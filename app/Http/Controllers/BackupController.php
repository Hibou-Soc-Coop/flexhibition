<?php

namespace App\Http\Controllers;

use App\Facades\Settings;
use App\Http\Requests\BackupRestoreRequest;
use App\Http\Requests\BackupSettingsRequest;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function index(): Response
    {
        $settings = $this->settingsPayload();
        $remoteDisk = $settings['remote_disk'];

        return Inertia::render('backend/Backups/Index', [
            'settings' => $settings,
            'available_disks' => $this->backupService->availableRemoteDisks(),
            'backups' => [
                'local' => $this->backupService->listBackups('local'),
                'remote' => $settings['remote_enabled'] && $remoteDisk
                    ? $this->backupService->listBackups($remoteDisk)
                    : [],
            ],
        ]);
    }

    public function store(): BinaryFileResponse|RedirectResponse
    {
        try {
            $path = $this->backupService->createBackup();

            return response()->download($path);
        } catch (\Exception $e) {
            return back()->with('error', 'Backup failed: '.$e->getMessage());
        }
    }

    public function restore(BackupRestoreRequest $request): RedirectResponse
    {
        $file = $request->file('backup_file');
        $checksum = $request->file('checksum_file');

        $timestamp = time();
        $tempPath = $file->storeAs('backups/uploads', "restore_upload_{$timestamp}.zip", 'local');
        $checksumPath = $checksum
            ? $checksum->storeAs('backups/uploads', "restore_upload_{$timestamp}.sha256", 'local')
            : null;

        $absolutePath = Storage::disk('local')->path($tempPath);
        $checksumAbsolutePath = $checksumPath ? Storage::disk('local')->path($checksumPath) : null;

        try {
            $this->backupService->restoreBackup($absolutePath, $checksumAbsolutePath);

            Storage::disk('local')->delete($tempPath);
            if ($checksumPath) {
                Storage::disk('local')->delete($checksumPath);
            }

            return back()->with('success', 'System restored successfully.');
        } catch (\Exception $e) {
            Storage::disk('local')->delete($tempPath);
            if ($checksumPath) {
                Storage::disk('local')->delete($checksumPath);
            }

            return back()->with('error', 'Restore failed: '.$e->getMessage());
        }
    }

    public function updateSettings(BackupSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Settings::set('backups.remote_enabled', (bool) $validated['remote_enabled']);
        Settings::set('backups.remote_disk', $validated['remote_disk'] ?? 'google');
        Settings::set('backups.retention_days', (int) $validated['retention_days']);
        Settings::set('backups.checksum_enabled', (bool) $validated['checksum_enabled']);
        Settings::set('backups.schedule_enabled', (bool) $validated['schedule_enabled']);
        Settings::set('backups.schedule_cron', $validated['schedule_cron'] ?? '');

        return back()->with('success', 'Backup settings updated successfully.');
    }

    public function download(string $disk, string $file): StreamedResponse|RedirectResponse
    {
        if (! $this->isAllowedDisk($disk)) {
            return back()->with('error', 'Invalid backup disk.');
        }

        if (! $this->isValidBackupFileName($file)) {
            return back()->with('error', 'Invalid backup filename.');
        }

        $path = 'backups/'.$file;
        if (! Storage::disk($disk)->exists($path)) {
            return back()->with('error', 'Backup file not found.');
        }

        return response()->streamDownload(function () use ($disk, $path) {
            $stream = Storage::disk($disk)->readStream($path);

            if ($stream === false) {
                throw new \RuntimeException('Unable to stream backup file.');
            }

            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $file);
    }

    protected function settingsPayload(): array
    {
        return [
            'remote_enabled' => (bool) Settings::get('backups.remote_enabled', true),
            'remote_disk' => (string) Settings::get('backups.remote_disk', 'google'),
            'retention_days' => (int) Settings::get('backups.retention_days', 30),
            'checksum_enabled' => (bool) Settings::get('backups.checksum_enabled', true),
            'schedule_enabled' => (bool) Settings::get('backups.schedule_enabled', false),
            'schedule_cron' => (string) Settings::get('backups.schedule_cron', '0 2 * * *'),
        ];
    }

    protected function isAllowedDisk(string $disk): bool
    {
        $allowed = array_merge(['local'], $this->backupService->availableRemoteDisks());

        return in_array($disk, $allowed, true);
    }

    protected function isValidBackupFileName(string $file): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9._-]+\.zip$/', $file);
    }
}
