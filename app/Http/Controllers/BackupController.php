<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function index()
    {
        return Inertia::render('backend/Backups/Index');
    }

    public function store()
    {
        try {
            $path = $this->backupService->createBackup();
            return response()->download($path)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return back()->with('error', 'Backup failed: '.$e->getMessage());
        }
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:zip',
        ]);

        $file = $request->file('backup_file');

        // Move file to a temporary location we can access
        $tempPath = $file->storeAs('backups/uploads', 'restore_upload_'.time().'.zip', 'local');
        $absolutePath = storage_path('app/private/'.$tempPath);

        try {
            $this->backupService->restoreBackup($absolutePath);

            // Clean up upload
            Storage::disk('local')->delete($tempPath);

            return back()->with('success', 'System restored successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Restore failed: '.$e->getMessage());
        }
    }
}
