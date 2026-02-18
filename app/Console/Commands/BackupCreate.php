<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a full backup of database and media files';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info('Starting backup process...');

        try {
            $path = $backupService->createBackup();
            $this->info("Backup created successfully at: {$path}");
        } catch (\Exception $e) {
            $this->error('Backup failed: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
