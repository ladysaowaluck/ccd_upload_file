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

        if ($customFileName) {
            $fileName = $customFileName;
        } else {
            $cleanOriginal = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $fileName = time() . '_' . $cleanOriginal;
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

        // Gemini : Commented out - local Linux server (/mnt/efs) upload disabled. S3 is the sole upload target.
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

        // Gemini : Commented out - local server path removed. Returns S3-only locations.
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
            Storage::disk($this->cloudDisk)->put($testPath, 'probe');
            Storage::disk($this->cloudDisk)->delete($testPath);

            return [
                'connected' => true,
                'bucket' => $bucket,
                'region' => $region,
                'message' => "Successfully connected to AWS S3 bucket [{$bucket}] ({$region})",
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'bucket' => $this->getCloudBucket(),
                'region' => $this->getCloudRegion(),
                'message' => 'AWS S3 Connection Error: ' . $e->getMessage(),
            ];
        }
    }

    public function exists(string $path): bool
    {
        $cleanPath = ltrim($path, '/');

        // $serverFile = "{$this->serverStoragePath}/{$cleanPath}";
        // if (file_exists($serverFile)) {
        //     return true;
        // }

        /*
        if (Storage::disk($this->localDisk)->exists($cleanPath)) {
            return true;
        }
        */

        // Check AWS S3 cloud bucket.
        if ($this->isCloudConfigured()) {
            return Storage::disk($this->cloudDisk)->fileExists($cleanPath);
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

        // Gemini : Commented out - local Linux server (/mnt/efs) deletion disabled. S3 only.
        // $serverFile = "{$this->serverStoragePath}/{$cleanPath}";
        // if (file_exists($serverFile)) {
        //     $deleted = @unlink($serverFile) || $deleted;
        // }
        //
        // // Also delete /mnt/efs/evidence mirror if applicable.
        // $evidenceMirror = "{$this->serverStoragePath}/evidence/" . basename($cleanPath);
        // if (file_exists($evidenceMirror)) {
        //     @unlink($evidenceMirror);
        // }

        /*
        if (Storage::disk($this->localDisk)->exists($cleanPath)) {
            $deleted = Storage::disk($this->localDisk)->delete($cleanPath) || $deleted;
        }
        */

        // Delete from AWS S3 cloud bucket if configured.
        if ($this->isCloudConfigured() && Storage::disk($this->cloudDisk)->fileExists($cleanPath)) {
            $deleted = Storage::disk($this->cloudDisk)->delete($cleanPath) || $deleted;
        }

        return $deleted;
    }

    // Stream and download document file directly from AWS S3 bucket.
    public function download(string $relativePath, ?string $downloadName = null)
    {
        $cleanPath = ltrim($relativePath, '/');
        $fileName = $downloadName ?? basename($cleanPath);

        // Gemini : Commented out - local Linux server (/mnt/efs) download disabled. S3 is primary source.
        // $serverFile = "{$this->serverStoragePath}/{$cleanPath}";
        // if (file_exists($serverFile) && is_readable($serverFile)) {
        //     return response()->download($serverFile, $fileName);
        // }

        /*
        if (Storage::disk($this->localDisk)->exists($cleanPath)) {
            return Storage::disk($this->localDisk)->download($cleanPath, $fileName);
        }
        */

        // Download from AWS S3 cloud bucket.
        if ($this->isCloudConfigured() && Storage::disk($this->cloudDisk)->fileExists($cleanPath)) {
            return Storage::disk($this->cloudDisk)->download($cleanPath, $fileName);
        }

        abort(404, "Document '{$fileName}' was not found on AWS S3 bucket.");
    }
}
