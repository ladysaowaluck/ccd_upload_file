<?php

namespace App\Services\Storage;

// Gemini : DocumentStorageService dedicated exclusively to AWS S3 bucket storage with terminal DEBUG echo
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentStorageService
{
    // Lady : Local disk storage is deprecated per requirement to focus exclusively on AWS S3 Bucket storage.
    // Storing files locally consumes local server storage and leads to desynchronization in multi-instance environments.
    // protected string $localDisk = 'public';

    // Gemini : Centralize all document storage operations on AWS S3 cloud disk
    protected string $cloudDisk = 's3';

    // Gemini : Track uploaded file debug info during the request lifecycle
    protected array $uploadHistory = [];

    /**
     * Store an uploaded document file directly to AWS S3 bucket only (local storage bypassed).
     *
     * @param UploadedFile $file
     * @param string $directory (e.g. 'reference', 'hs_evidence')
     * @param string|null $customFileName
     * @return string Stored relative path (e.g. 'reference/filename.pdf')
     */
    public function store(UploadedFile $file, string $directory, ?string $customFileName = null): string
    {
        $directory = trim($directory, '/');
        
        if ($customFileName) {
            $fileName = $customFileName;
        } else {
            $cleanOriginal = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $fileName = time() . '_' . $cleanOriginal;
        }

        // Lady : Local disk storage is deprecated per user requirement to focus exclusively on AWS S3 Bucket storage.
        /*
        $storedPath = $file->storeAs($directory, $fileName, $this->localDisk);
        if (!$storedPath) {
            $storedPath = "{$directory}/{$fileName}";
        }
        $s3Synced = $this->syncToCloud($storedPath, $directory, $fileName, $file->getRealPath());
        */

        // Gemini : Stream and store uploaded file directly to AWS S3 bucket only
        $s3Key = "{$directory}/{$fileName}";
        $storedPath = $s3Key;
        $s3Synced = false;
        $bucket = $this->getCloudBucket();
        $region = $this->getCloudRegion();
        $fileSize = $file->getSize() ?: (file_exists($file->getRealPath()) ? filesize($file->getRealPath()) : 0);

        if ($this->isCloudConfigured()) {
            try {
                $realPath = $file->getRealPath();
                if ($realPath && file_exists($realPath) && !is_dir($realPath)) {
                    $stream = fopen($realPath, 'r');
                    if ($stream) {
                        $s3Synced = (bool) Storage::disk($this->cloudDisk)->put($s3Key, $stream);
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error("DocumentStorageService: Direct S3 upload failed: {$e->getMessage()}", [
                    'key' => $s3Key,
                ]);
            }
        }

        $s3Location = $bucket ? "s3://{$bucket}/{$s3Key}" : "s3://unknown/{$s3Key}";
        $s3Url = ($bucket && $s3Synced) ? Storage::disk($this->cloudDisk)->url($s3Key) : null;

        // Gemini : Output terminal DEBUG log for developer visibility on CLI / console
        $statusText = $s3Synced ? 'SUCCESS' : 'FAILED';
        echo PHP_EOL . "================================================================================" . PHP_EOL;
        echo " [DEBUG S3 UPLOAD] Status: {$statusText}" . PHP_EOL;
        echo " - Target S3 URI : {$s3Location}" . PHP_EOL;
        echo " - File Name     : {$fileName}" . PHP_EOL;
        echo " - Size          : {$fileSize} bytes" . PHP_EOL;
        echo " - Storage Mode  : AWS S3 Bucket Only (Local storage eliminated)" . PHP_EOL;
        echo "================================================================================" . PHP_EOL;

        $debugInfo = [
            'file_name'     => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes'    => $fileSize,
            'directory'     => $directory,
            'local_path'    => 'Bypassed (Cloud-Only)',
            'local_url'     => null,
            's3_synced'     => $s3Synced,
            's3_bucket'     => $bucket,
            's3_region'     => $region,
            's3_location'   => $s3Location,
            's3_url'        => $s3Url,
        ];

        $this->uploadHistory[] = $debugInfo;

        Log::info("DocumentStorage: Uploaded directly to S3", $debugInfo);

        return $storedPath;
    }

    // Gemini : Accessors for upload debug history and file locations
    public function getUploadHistory(): array
    {
        return $this->uploadHistory;
    }

    public function clearUploadHistory(): void
    {
        $this->uploadHistory = [];
    }

    public function getFileLocations(string $relativePath): array
    {
        $cleanPath = ltrim($relativePath, '/');
        $bucket = $this->getCloudBucket();

        // Lady : Local file paths are deprecated as files are uploaded directly to AWS S3 bucket only.
        /*
        return [
            'local_path'    => Storage::disk($this->localDisk)->path($cleanPath),
            'local_url'     => Storage::disk($this->localDisk)->url($cleanPath),
            's3_location'   => $bucket ? "s3://{$bucket}/{$cleanPath}" : null,
            's3_url'        => ($bucket && $this->isCloudConfigured()) ? Storage::disk($this->cloudDisk)->url($cleanPath) : null,
        ];
        */

        // Gemini : Return AWS S3 bucket as primary storage location (local bypassed)
        return [
            'local_path'    => 'Bypassed (Cloud-Only)',
            'local_url'     => null,
            's3_location'   => $bucket ? "s3://{$bucket}/{$cleanPath}" : null,
            's3_url'        => ($bucket && $this->isCloudConfigured()) ? Storage::disk($this->cloudDisk)->url($cleanPath) : null,
        ];
    }

    // Lady : syncToCloud is deprecated because uploads now stream directly to S3 without local intermediate write.
    /*
    protected function syncToCloud(string $relativePath, string $directory, string $fileName, ?string $sourceRealPath = null): bool
    {
        if (!$this->isCloudConfigured()) {
            return false;
        }

        try {
            $source = $sourceRealPath;
            if (!$source || !file_exists($source) || is_dir($source)) {
                $localFullPath = Storage::disk($this->localDisk)->path($relativePath);
                if (file_exists($localFullPath) && !is_dir($localFullPath)) {
                    $source = $localFullPath;
                }
            }

            if ($source && file_exists($source) && !is_dir($source)) {
                $stream = fopen($source, 'r');
                $result = Storage::disk($this->cloudDisk)->put("{$directory}/{$fileName}", $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                return (bool) $result;
            }
        } catch (\Throwable $e) {
            Log::warning("DocumentStorageService: Failed to sync file to S3: {$e->getMessage()}", [
                'path' => $relativePath,
            ]);
        }

        return false;
    }
    */

    /**
     * Check if S3 cloud storage is configured with required credentials.
     */
    public function isCloudConfigured(): bool
    {
        return !empty(config('filesystems.disks.s3.key'))
            && !empty(config('filesystems.disks.s3.bucket'));
    }

    // Gemini : AWS S3 metadata helpers for UI display and connection status check
    public function getCloudBucket(): ?string
    {
        return config('filesystems.disks.s3.bucket');
    }

    public function getCloudRegion(): ?string
    {
        return config('filesystems.disks.s3.region');
    }

    /**
     * Verify live connectivity to AWS S3 using credentials from .env.
     */
    public function testCloudConnection(): array
    {
        if (!$this->isCloudConfigured()) {
            return [
                'connected' => false,
                'bucket'    => null,
                'region'    => null,
                'message'   => 'AWS credentials or bucket not configured in .env',
            ];
        }

        try {
            $bucket = $this->getCloudBucket();
            $region = $this->getCloudRegion();
            // Gemini : Perform a probe write to reference/.probe to verify S3 PutObject and DeleteObject
            // Using reference/ prefix because bucket IAM policy permits reference/* and hs_evidence/*
            $testPath = 'reference/.probe_' . date('Ymd_His');
            Storage::disk($this->cloudDisk)->put($testPath, 'probe');
            Storage::disk($this->cloudDisk)->delete($testPath);

            return [
                'connected' => true,
                'bucket'    => $bucket,
                'region'    => $region,
                'message'   => "Successfully connected to AWS S3 bucket [{$bucket}] ({$region})",
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'bucket'    => $this->getCloudBucket(),
                'region'    => $this->getCloudRegion(),
                'message'   => 'AWS S3 Connection Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if a file exists on AWS S3 cloud storage.
     */
    public function exists(string $path): bool
    {
        // Lady : Checking local disk is deprecated because storage focuses exclusively on AWS S3 bucket.
        /*
        if (Storage::disk($this->localDisk)->exists($path)) {
            return true;
        }

        if ($this->isCloudConfigured() && Storage::disk($this->cloudDisk)->exists($path)) {
            return true;
        }
        */

        // Gemini : Check AWS S3 bucket using fileExists() to use HeadObject without requiring ListBucket IAM permission
        if ($this->isCloudConfigured()) {
            return Storage::disk($this->cloudDisk)->fileExists($path);
        }

        return false;
    }

    /**
     * Get the URL for a file from AWS S3 bucket.
     */
    public function getUrl(string $path): string
    {
        // Lady : Reading from local storage disk is deprecated.
        /*
        if (Storage::disk($this->localDisk)->exists($path)) {
            return Storage::disk($this->localDisk)->url($path);
        }
        return asset('storage/' . ltrim($path, '/'));
        */

        // Gemini : Return direct AWS S3 URL for stored bucket object
        if ($this->isCloudConfigured()) {
            return Storage::disk($this->cloudDisk)->url($path);
        }

        return '';
    }

    /**
     * Delete a file directly from AWS S3 bucket.
     */
    public function delete(string $path): bool
    {
        // Lady : Local file deletion is deprecated because documents are not stored on local disk.
        /*
        $deleted = Storage::disk($this->localDisk)->delete($path);
        if ($this->isCloudConfigured() && Storage::disk($this->cloudDisk)->exists($path)) {
            Storage::disk($this->cloudDisk)->delete($path);
        }
        return $deleted;
        */

        // Gemini : Directly delete object from AWS S3 bucket
        if ($this->isCloudConfigured() && Storage::disk($this->cloudDisk)->fileExists($path)) {
            return Storage::disk($this->cloudDisk)->delete($path);
        }

        return false;
    }

    // Gemini : Stream and download document file directly from AWS S3 bucket (with local fallback)
    public function download(string $relativePath, ?string $downloadName = null)
    {
        $cleanPath = ltrim($relativePath, '/');
        $fileName = $downloadName ?? basename($cleanPath);

        // Stream directly from AWS S3 cloud bucket
        if ($this->isCloudConfigured() && Storage::disk($this->cloudDisk)->fileExists($cleanPath)) {
            return Storage::disk($this->cloudDisk)->download($cleanPath, $fileName);
        }

        // Secondary fallback for legacy files stored on local public disk
        if (Storage::disk('public')->exists($cleanPath)) {
            return Storage::disk('public')->download($cleanPath, $fileName);
        }

        abort(404, "Document '{$fileName}' was not found in AWS S3 bucket or local storage.");
    }
}
