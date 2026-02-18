<?php

namespace App\Services;

use App\Facades\Settings;
use Exception;
use Illuminate\Http\File as HttpFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupService
{
    protected string $localDisk = 'local';

    protected string $backupDirectory = 'backups';

    /**
     * Create a backup of the database and media files.
     *
     * @return string Path to the backup zip file
     *
     * @throws Exception
     */
    public function createBackup(): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $zipFileName = "backup-{$timestamp}.zip";
        $workingDir = storage_path("app/private/{$this->backupDirectory}/temp_{$timestamp}");
        $payloadDir = $workingDir.'/payload';
        $zipPath = $workingDir.'/'.$zipFileName;

        if (! File::exists($payloadDir)) {
            File::makeDirectory($payloadDir, 0755, true);
        }

        try {
            $this->ensureSqliteDatabase();

            // 1. Backup Database (SQLite)
            $dbPath = database_path('database.sqlite');
            if (File::exists($dbPath)) {
                File::copy($dbPath, $payloadDir.'/database.sqlite');
            } else {
                throw new Exception("Database file not found at {$dbPath}");
            }

            // 2. Backup Media (Public Storage)
            $publicStoragePath = storage_path('app/public');
            if (File::exists($publicStoragePath)) {
                $this->copyDirectory($publicStoragePath, $payloadDir.'/public');
            }

            // 3. Zip It All
            $this->zipDirectory($payloadDir, $zipPath);

            $checksumContents = null;
            if ($this->checksumEnabled()) {
                $checksumContents = $this->checksumContents($zipPath, $zipFileName);
            }

            $relativePath = $this->backupDirectory.'/'.$zipFileName;
            $this->storeBackupToDisk($this->localDisk, $relativePath, $zipPath, $checksumContents);

            if ($this->remoteEnabled()) {
                $this->storeBackupToDisk($this->remoteDisk(), $relativePath, $zipPath, $checksumContents);
            }

        } catch (Exception $e) {
            // Cleanup on error
            File::deleteDirectory($workingDir);
            throw $e;
        }

        // Cleanup temp dir
        File::deleteDirectory($workingDir);

        $this->pruneBackups($this->localDisk);
        if ($this->remoteEnabled()) {
            $this->pruneBackups($this->remoteDisk());
        }

        return Storage::disk($this->localDisk)->path($relativePath);
    }

    /**
     * Restore the system from a backup zip file.
     *
     * @throws Exception
     */
    public function restoreBackup(string $zipFilePath, ?string $checksumFilePath = null): void
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $restoreDir = storage_path("app/private/{$this->backupDirectory}/restore_{$timestamp}");

        // Create restore directory
        if (! File::exists($restoreDir)) {
            File::makeDirectory($restoreDir, 0755, true);
        }

        try {
            $this->ensureSqliteDatabase();

            if ($this->checksumEnabled()) {
                $checksumPath = $checksumFilePath ?? $zipFilePath.'.sha256';
                if (! File::exists($checksumPath)) {
                    throw new Exception('Checksum file not found for the backup zip.');
                }
                $this->assertChecksumMatches($zipFilePath, $checksumPath);
            }

            // 1. Unzip
            $this->unzipFile($zipFilePath, $restoreDir);

            // 2. Validate Backup
            if (! File::exists($restoreDir.'/database.sqlite')) {
                throw new Exception('Invalid backup: database.sqlite not found.');
            }

            // 3. Restore Database
            // Close connection to allow file overwrite
            DB::disconnect();

            $currentDbPath = database_path('database.sqlite');
            $backupDbPath = database_path('database.sqlite.bak');

            // Backup current DB just in case
            if (File::exists($currentDbPath)) {
                File::copy($currentDbPath, $backupDbPath);
            }

            File::copy($restoreDir.'/database.sqlite', $currentDbPath);

            // 4. Restore Media
            $publicStoragePath = storage_path('app/public');

            // Only clean and restore if backup has public folder
            if (File::exists($restoreDir.'/public')) {
                // Clean current public storage
                if (File::exists($publicStoragePath)) {
                    File::cleanDirectory($publicStoragePath);
                }

                $this->copyDirectory($restoreDir.'/public', $publicStoragePath);
            }

        } catch (Exception $e) {
            File::deleteDirectory($restoreDir);
            throw $e;
        }

        // Cleanup
        File::deleteDirectory($restoreDir);
    }

    public function listBackups(string $disk): array
    {
        if (! Storage::disk($disk)->exists($this->backupDirectory)) {
            return [];
        }

        $files = Storage::disk($disk)->files($this->backupDirectory);
        $backups = [];

        foreach ($files as $path) {
            if (! str_ends_with($path, '.zip')) {
                continue;
            }

            $backups[] = [
                'disk' => $disk,
                'path' => $path,
                'name' => basename($path),
                'size' => Storage::disk($disk)->size($path),
                'last_modified' => Storage::disk($disk)->lastModified($path),
                'checksum_exists' => Storage::disk($disk)->exists($path.'.sha256'),
            ];
        }

        usort($backups, function (array $first, array $second) {
            return $second['last_modified'] <=> $first['last_modified'];
        });

        return $backups;
    }

    public function availableRemoteDisks(): array
    {
        $disks = array_keys(config('filesystems.disks', []));

        return array_values(array_filter($disks, function (string $disk) {
            return $disk !== $this->localDisk && $disk !== 'public';
        }));
    }

    protected function copyDirectory(string $source, string $destination): void
    {
        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }
        File::copyDirectory($source, $destination);
    }

    protected function zipDirectory(string $source, string $destination): void
    {
        if (! extension_loaded('zip') || ! file_exists($source)) {
            throw new Exception('Zip extension not available or source missing.');
        }

        $zip = new ZipArchive;
        if (! $zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new Exception("Cannot create zip file at {$destination}");
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (! $file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($source) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }

    protected function unzipFile(string $zipPath, string $destination): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($destination);
            $zip->close();
        } else {
            throw new Exception('Failed to open zip file');
        }
    }

    protected function storeBackupToDisk(
        string $disk,
        string $relativePath,
        string $zipPath,
        ?string $checksumContents
    ): void {
        $directory = dirname($relativePath);
        $fileName = basename($relativePath);

        $storedPath = Storage::disk($disk)->putFileAs($directory, new HttpFile($zipPath), $fileName);
        if ($storedPath === false) {
            throw new Exception("Failed to store backup on disk [{$disk}].");
        }

        if ($checksumContents !== null) {
            $checksumStored = Storage::disk($disk)->put($relativePath.'.sha256', $checksumContents);
            if ($checksumStored === false) {
                throw new Exception("Failed to store checksum on disk [{$disk}].");
            }
        }
    }

    protected function pruneBackups(string $disk): void
    {
        $retentionDays = $this->retentionDays();
        if ($retentionDays === null) {
            return;
        }

        $cutoff = Carbon::now()->subDays($retentionDays)->getTimestamp();
        $files = Storage::disk($disk)->files($this->backupDirectory);

        foreach ($files as $path) {
            if (! str_ends_with($path, '.zip')) {
                continue;
            }

            $lastModified = Storage::disk($disk)->lastModified($path);
            if ($lastModified < $cutoff) {
                Storage::disk($disk)->delete($path);
                Storage::disk($disk)->delete($path.'.sha256');
            }
        }
    }

    protected function checksumContents(string $zipPath, string $zipFileName): string
    {
        $hash = hash_file('sha256', $zipPath);

        return $hash.'  '.$zipFileName.PHP_EOL;
    }

    protected function assertChecksumMatches(string $zipPath, string $checksumPath): void
    {
        $expectedHash = trim(explode(' ', trim(File::get($checksumPath)))[0]);
        $actualHash = hash_file('sha256', $zipPath);

        if (! hash_equals($expectedHash, $actualHash)) {
            throw new Exception('Checksum verification failed for the backup zip.');
        }
    }

    protected function ensureSqliteDatabase(): void
    {
        if (config('database.default') !== 'sqlite') {
            throw new Exception('Backup service currently supports SQLite only.');
        }
    }

    protected function retentionDays(): ?int
    {
        $value = Settings::get('backups.retention_days', 30);
        $days = is_numeric($value) ? (int) $value : null;

        return $days !== null && $days > 0 ? $days : null;
    }

    protected function checksumEnabled(): bool
    {
        return (bool) Settings::get('backups.checksum_enabled', true);
    }

    protected function remoteEnabled(): bool
    {
        return (bool) Settings::get('backups.remote_enabled', true);
    }

    protected function remoteDisk(): string
    {
        $disk = Settings::get('backups.remote_disk', 'google');

        return is_string($disk) && $disk !== '' ? $disk : 'google';
    }
}
