<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupRestore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore {file : The path to the backup zip file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore the system from a backup zip file';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService)
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        if (! $this->confirm('This will overwrite current database and media. Are you sure?')) {
            return 0;
        }

        $this->info('Starting restore process...');

        try {
            $backupService->restoreBackup($file);
            $this->info("System restored successfully.");

            $this->call('cache:clear');
            $this->info("Cache cleared.");

        } catch (\Exception $e) {
            $this->error("Restore failed: ".$e->getMessage());
            return 1;
        }

        return 0;
    }
}
