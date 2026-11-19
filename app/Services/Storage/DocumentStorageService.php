<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentStorageService
{
    protected string $cloudDisk = 's3';
    protected string $localDisk = 'public';
    protected string $serverStoragePath = '/mnt/efs';

    public function __construct(?string $serverStoragePath = null)
    {
        $this->serverStoragePath = $serverStoragePath ?? env('SERVER_STORAGE_PATH', '/mnt/efs');
    }

    protected array $uploadHistory = [];

    /**
     * @param UploadedFile $file
     * @param string $directory (e.g. 'reference', 'hs_evidence')
     * @param string|null $customFileName
     * @return string Stored relative path (e.g. 'reference/filename.pdf')
     */
    public function store(UploadedFile $file, string $directory, ?string $customFileName = null): string
    {
        $directory = trim($directory, '/');

        // Sanitize and bound filename length to prevent S3 key issues and database varchar(191) truncation
        if ($customFileName) {
            $ext = pathinfo($customFileName, PATHINFO_EXTENSION);
            $nameWithoutExt = pathinfo($customFileName, PATHINFO_FILENAME);
            $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nameWithoutExt);
            $cleanName = substr($cleanName, 0, 150);
            $fileName = $ext ? "{$cleanName}.{$ext}" : $cleanName;
        } else {
            $ext = $file->getClientOriginalExtension();
            $nameWithoutExt = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $cleanOriginal = substr(preg_replace('/[^A-Za-z0-9_\-]/', '_', $nameWithoutExt), 0, 130);
            $fileName = time() . '_' . $cleanOriginal . ($ext ? ".{$ext}" : '');
        }

        /*
        $storedPath = $file->storeAs($directory, $fileName, $this->localDisk);
        if (!$storedPath) {
            $storedPath = "{$directory}/{$fileName}";
        }
        $s3Synced = $this->syncToCloud($storedPath, $directory, $fileName, $file->getRealPath());
        */
        $relativePath = "{$directory}/{$fileName}";
        $s3Key = $relativePath;
        $storedPath = $relativePath;
        $s3Synced = false;
        $bucket = $this->getCloudBucket();
        $region = $this->getCloudRegion();
        $fileSize = $file->getSize() ?: (file_exists($file->getRealPath()) ? filesize($file->getRealPath()) : 0);

        // 1. Upload to AWS S3 bucket only.
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

        // $serverLocation = "{$this->serverStoragePath}/{$directory}/{$fileName}";
        // if (is_dir($this->serverStoragePath)) {
        //     try {
        //         $targetDir = "{$this->serverStoragePath}/{$directory}";
        //         if (!is_dir($targetDir)) {
        //             @mkdir($targetDir, 0775, true);
        //         }
        //         $serverSaved = (bool) @copy($file->getRealPath(), $serverLocation);
        //
        //         // Mirror to /mnt/efs/evidence if stored in hs_evidence for backward compatibility.
        //         if ($serverSaved && $directory === 'hs_evidence') {
        //             $evidenceDir = "{$this->serverStoragePath}/evidence";
        //             if (!is_dir($evidenceDir)) {
        //                 @mkdir($evidenceDir, 0775, true);
        //             }
        //             @copy($serverLocation, "{$evidenceDir}/{$fileName}");
        //         }
        //     } catch (\Throwable $e) {
        //         Log::warning("DocumentStorageService: Direct /mnt/efs save failed: {$e->getMessage()}", [
        //             'path' => $serverLocation,
        //         ]);
        //     }
        // }

        /*
        try {
            $realPath = $file->getRealPath();
            if ($realPath && file_exists($realPath) && !is_dir($realPath)) {
                $stream = fopen($realPath, 'r');
                if ($stream) {
                    $localSaved = (bool) Storage::disk($this->localDisk)->put($relativePath, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            }
            if (!$localSaved) {
                $storedLocal = Storage::disk($this->localDisk)->putFileAs($directory, $file, $fileName);
                $localSaved = (bool) $storedLocal;
            }
        } catch (\Throwable $e) {
            Log::error("DocumentStorageService: Local storage save failed: {$e->getMessage()}", [
                'path' => $relativePath,
            ]);
        }

        // Also mirror to 'evidence' if stored in 'hs_evidence' for dual-folder compatibility
        if ($localSaved && $directory === 'hs_evidence') {
            try {
                $evidencePath = "evidence/{$fileName}";
                Storage::disk($this->localDisk)->copy($relativePath, $evidencePath);
            } catch (\Throwable) {
                // Secondary mirror copy optional
            }
        }
        */

        $s3Location = $bucket ? "s3://{$bucket}/{$s3Key}" : "s3://unknown/{$s3Key}";
        $s3Url = ($bucket && $s3Synced) ? Storage::disk($this->cloudDisk)->url($s3Key) : null;

        // S3-only status output for CLI/artisan runs. Web requests are silent.
        $s3StatusText = $s3Synced ? 'SUCCESS' : 'FAILED / SKIPPED';
        if (app()->runningInConsole()) {
            echo PHP_EOL . "=================================================================================" . PHP_EOL;
            echo " [DEBUG STORAGE UPLOAD]" . PHP_EOL;
            echo " - File Name        : {$fileName}" . PHP_EOL;
            echo " - Size             : {$fileSize} bytes" . PHP_EOL;
            echo " - 1. AWS Bucket    : [{$s3StatusText}] {$s3Location}" . PHP_EOL;
            echo " -    Local Server  : DISABLED (commented out - S3 only)" . PHP_EOL;
            echo "=================================================================================" . PHP_EOL;
        }

        // S3-only upload info. local_path key kept for backward compatibility.
        $debugInfo = [
            'file_name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $fileSize,
            'directory' => $directory,
            'local_path' => 'DISABLED (S3 only)',
            'local_url' => null,
            's3_synced' => $s3Synced,
            's3_bucket' => $bucket,
            's3_region' => $region,
            's3_location' => $s3Location,
            's3_url' => $s3Url,
        ];

        $this->uploadHistory[] = $debugInfo;

        Log::info("DocumentStorage: File uploaded to AWS S3 (local server /mnt/efs is disabled).", $debugInfo);

        return $storedPath;
    }

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

        /*
        return [
            'local_path'    => Storage::disk($this->localDisk)->path($cleanPath),
            'local_url'     => Storage::disk($this->localDisk)->url($cleanPath),
            's3_location'   => $bucket ? "s3://{$bucket}/{$cleanPath}" : null,
            's3_url'        => ($bucket && $this->isCloudConfigured()) ? Storage::disk($this->cloudDisk)->url($cleanPath) : null,
        ];
        */

        /*
        return [
            'local_path' => 'Bypassed (Cloud-Only)',
            'local_url' => null,
            's3_location' => $bucket ? "s3://{$bucket}/{$cleanPath}" : null,
            's3_url' => ($bucket && $this->isCloudConfigured()) ? Storage::disk($this->cloudDisk)->url($cleanPath) : null,
        ];
        */

        /*
        return [
            'local_path'   => Storage::disk($this->localDisk)->path($cleanPath),
            'local_url'    => Storage::disk($this->localDisk)->url($cleanPath),
            'local_server' => $serverPath,
            's3_location'  => $bucket ? "s3://{$bucket}/{$cleanPath}" : null,
            's3_url'       => ($bucket && $this->isCloudConfigured()) ? Storage::disk($this->cloudDisk)->url($cleanPath) : null,
        ];
        */

        // $serverPath = is_dir($this->serverStoragePath)
        //     ? "{$this->serverStoragePath}/{$cleanPath}"
        //     : "/mnt/efs/{$cleanPath}";
        //
        // return [
        //     'local_server' => $serverPath,
        //     'local_path'   => $serverPath,
        //     'local_url'    => null,
        //     's3_location'  => $bucket ? "s3://{$bucket}/{$cleanPath}" : null,
        //     's3_url'       => ($bucket && $this->isCloudConfigured()) ? Storage::disk($this->cloudDisk)->url($cleanPath) : null,
        // ];

        return [
            'local_path' => 'DISABLED (S3 only)',
            'local_url' => null,
            's3_location' => $bucket ? "s3://{$bucket}/{$cleanPath}" : null,
            's3_url' => ($bucket && $this->isCloudConfigured()) ? Storage::disk($this->cloudDisk)->url($cleanPath) : null,
        ];
    }

    public function isCloudConfigured(): bool
    {
        return !empty(config('filesystems.disks.s3.key'))
            && !empty(config('filesystems.disks.s3.bucket'));
    }

    public function getCloudBucket(): ?string
    {
        return config('filesystems.disks.s3.bucket');
    }

    public function getCloudRegion(): ?string
    {
        return config('filesystems.disks.s3.region');
    }

    public function testCloudConnection(): array
    {
        if (!$this->isCloudConfigured()) {
            return [
                'connected' => false,
                'bucket' => null,
                'region' => null,
                'message' => 'AWS credentials or bucket not configured in .env',
            ];
        }

        try {
            $bucket = $this->getCloudBucket();
            $region = $this->getCloudRegion();
            $testPath = 'reference/.probe_' . date('Ymd_His');
            // Explicitly verify put() return boolean since throw is false in filesystems.php
            $putSuccess = Storage::disk($this->cloudDisk)->put($testPath, 'probe');
            if (!$putSuccess) {
                return [
                    'connected' => false,
                    'bucket' => $bucket,
                    'region' => $region,
                    'message' => "AWS S3 write test failed. The bucket [{$bucket}] rejected the write probe (check IAM policy or server IP whitelist).",
                ];
            }
            Storage::disk($this->cloudDisk)->delete($testPath);

            return [
                'connected' => true,
                'bucket' => $bucket,
                'region' => $region,
                'message' => "Successfully connected to AWS S3 bucket [{$bucket}] ({$region})",
            ];
        } catch (\Throwable $e) {
            // s Capture detailed underlying exception message for S3 diagnostic
            $prev = $e->getPrevious();
            $detail = $prev ? ' | Detail: ' . $prev->getMessage() : '';
            return [
                'connected' => false,
                'bucket' => $this->getCloudBucket(),
                'region' => $this->getCloudRegion(),
                'message' => 'AWS S3 Connection Error: ' . $e->getMessage() . $detail,
            ];
        }
    }

    public function exists(string $path): bool
    {
        $cleanPath = ltrim($path, '/');

        // Check AWS S3 cloud bucket.
        if ($this->isCloudConfigured()) {
            // Defensive try-catch to prevent uncaught S3 exceptions from causing 500 errors
            try {
                return Storage::disk($this->cloudDisk)->fileExists($cleanPath);
            } catch (\Throwable $e) {
                Log::warning("DocumentStorageService: exists check failed on S3 for [{$cleanPath}]: " . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    // Get the URL for a file from AWS S3 bucket.
    public function getUrl(string $path): string
    {
        $cleanPath = ltrim($path, '/');

        /*
        if (Storage::disk($this->localDisk)->exists($cleanPath)) {
            return Storage::disk($this->localDisk)->url($cleanPath);
        }
        */

        // Serve URL from AWS S3 cloud bucket if configured.
        if ($this->isCloudConfigured()) {
            return Storage::disk($this->cloudDisk)->url($cleanPath);
        }

        return asset('storage/' . $cleanPath);
    }

    // Delete a file from AWS S3 bucket only.
    public function delete(string $path): bool
    {
        $cleanPath = ltrim($path, '/');
        $deleted = false;

        // Delete from AWS S3 cloud bucket if configured.
        if ($this->isCloudConfigured()) {
            // Wrap S3 deletion in defensive try-catch so failing to delete an old S3 object never causes a 500 error
            try {
                if (Storage::disk($this->cloudDisk)->fileExists($cleanPath)) {
                    $deleted = (bool) Storage::disk($this->cloudDisk)->delete($cleanPath);
                }
            } catch (\Throwable $e) {
                Log::warning("DocumentStorageService: Could not delete S3 file [{$cleanPath}]: " . $e->getMessage());
                $deleted = false;
            }
        }

        return $deleted;
    }

    // Generate a temporary pre-signed S3 URL for inline browser viewing (opens PDF/images in tab).
    public function getTemporaryUrl(string $path, int $minutesExpiry = 5): ?string
    {
        $cleanPath = ltrim($path, '/');
        if ($this->isCloudConfigured()) {
            // Safely guard temporaryUrl against S3 connectivity or permission exceptions
            try {
                if (Storage::disk($this->cloudDisk)->fileExists($cleanPath)) {
                    return Storage::disk($this->cloudDisk)->temporaryUrl($cleanPath, now()->addMinutes($minutesExpiry));
                }
            } catch (\Throwable $e) {
                Log::warning("DocumentStorageService: getTemporaryUrl failed on S3 for [{$cleanPath}]: " . $e->getMessage());
                return null;
            }
        }
        return null;
    }

    // Stream and download document file directly from AWS S3 bucket.
    public function download(string $relativePath, ?string $downloadName = null)
    {
        $cleanPath = ltrim($relativePath, '/');
        $fileName = $downloadName ?? basename($cleanPath);

        // $serverFile = "{$this->serverStoragePath}/{$cleanPath}";
        // if (file_exists($serverFile) && is_readable($serverFile)) {
        //     return response()->download($serverFile, $fileName);
        // }

        /*
        if (Storage::disk($this->localDisk)->exists($cleanPath)) {
            return Storage::disk($this->localDisk)->download($cleanPath, $fileName);
        }
        */

        // Wrap S3 fileExists+download in try-catch — production S3 403 returns a
        // plain-text body that Flysystem cannot parse as XML, causing an unhandled exception → 500.
        // We convert that to a 503 (service temporarily unavailable) instead.
        if ($this->isCloudConfigured()) {
            try {
                if (Storage::disk($this->cloudDisk)->fileExists($cleanPath)) {
                    return Storage::disk($this->cloudDisk)->download($cleanPath, $fileName);
                }
            } catch (\Throwable $e) {
                Log::warning("DocumentStorageService: S3 download failed for [{$cleanPath}]: " . $e->getMessage());
                abort(503, 'File temporarily unavailable. Please try again later.');
            }
        }

        abort(404, "Document '{$fileName}' was not found on AWS S3 bucket.");
    }
}
