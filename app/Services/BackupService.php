<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Exception;

class BackupService
{
    protected string $disk = 'local';
    protected string $backupPath = 'backups';

    /**
     * Create a backup of the database and media files.
     *
     * @return string Path to the backup zip file
     * @throws Exception
     */
    public function createBackup(): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $tempDir = storage_path("app/{$this->backupPath}/temp_{$timestamp}");
        $zipFileName = "backup-{$timestamp}.zip";
        $zipPath = storage_path("app/{$this->backupPath}/{$zipFileName}");

        // Create temp directory
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        try {
            // 1. Backup Database (SQLite)
            $dbPath = database_path('database.sqlite');
            if (File::exists($dbPath)) {
                // Determine if we need to close connection or just copy
                // For SQLite file copy is usually sufficient if not under heavy write load
                File::copy($dbPath, $tempDir.'/database.sqlite');
            } else {
                throw new Exception("Database file not found at {$dbPath}");
            }

            // 2. Backup Media (Public Storage)
            $publicStoragePath = storage_path('app/public');
            if (File::exists($publicStoragePath)) {
                $this->copyDirectory($publicStoragePath, $tempDir.'/public');
            }

            // 3. Zip It All
            $this->zipDirectory($tempDir, $zipPath);

        } catch (Exception $e) {
            // Cleanup on error
            File::deleteDirectory($tempDir);
            throw $e;
        }

        // Cleanup temp dir
        File::deleteDirectory($tempDir);

        return $zipPath;
    }

    /**
     * Restore the system from a backup zip file.
     *
     * @param string $zipFilePath
     * @return void
     * @throws Exception
     */
    public function restoreBackup(string $zipFilePath): void
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $restoreDir = storage_path("app/{$this->backupPath}/restore_{$timestamp}");

        // Create restore directory
        if (! File::exists($restoreDir)) {
            File::makeDirectory($restoreDir, 0755, true);
        }

        try {
            // 1. Unzip
            $this->unzipFile($zipFilePath, $restoreDir);

            // 2. Validate Backup
            if (! File::exists($restoreDir.'/database.sqlite')) {
                throw new Exception("Invalid backup: database.sqlite not found.");
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

    protected function copyDirectory($source, $destination)
    {
        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }
        File::copyDirectory($source, $destination);
    }

    protected function zipDirectory($source, $destination)
    {
        if (! extension_loaded('zip') || ! file_exists($source)) {
            return false;
        }

        $zip = new ZipArchive();
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

    protected function unzipFile($zipPath, $destination)
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($destination);
            $zip->close();
        } else {
            throw new Exception("Failed to open zip file");
        }
    }
}
