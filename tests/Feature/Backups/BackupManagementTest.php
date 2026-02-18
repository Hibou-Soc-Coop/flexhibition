<?php

namespace Tests\Feature\Backups;

use App\Facades\Settings;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_settings_can_be_updated(): void
    {
        $user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'manage backups']);
        $user->givePermissionTo('manage backups');

        $response = $this->actingAs($user)->put(route('backups.settings.update'), [
            'remote_enabled' => true,
            'remote_disk' => 'google',
            'retention_days' => 14,
            'checksum_enabled' => true,
            'schedule_enabled' => true,
            'schedule_cron' => '0 3 * * *',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertTrue((bool) Settings::get('backups.remote_enabled'));
        $this->assertSame('google', Settings::get('backups.remote_disk'));
        $this->assertSame(14, (int) Settings::get('backups.retention_days'));
        $this->assertTrue((bool) Settings::get('backups.checksum_enabled'));
        $this->assertTrue((bool) Settings::get('backups.schedule_enabled'));
        $this->assertSame('0 3 * * *', Settings::get('backups.schedule_cron'));
    }

    public function test_backup_service_creates_zip_and_checksum(): void
    {
        Settings::set('backups.remote_enabled', false);
        Settings::set('backups.checksum_enabled', true);
        Settings::set('backups.retention_days', 30);

        $dbPath = database_path('database.sqlite');
        File::put($dbPath, 'test');

        $publicPath = storage_path('app/public');
        File::ensureDirectoryExists($publicPath);
        File::put($publicPath.'/sample.txt', 'media');

        $service = app(BackupService::class);
        $backupPath = $service->createBackup();

        $this->assertFileExists($backupPath);
        $this->assertFileExists($backupPath.'.sha256');

        File::delete($backupPath);
        File::delete($backupPath.'.sha256');
        Storage::disk('local')->deleteDirectory('backups');
        File::delete($publicPath.'/sample.txt');
        File::delete($dbPath);
    }

    public function test_backup_creation_route_creates_backup_without_download(): void
    {
        Settings::set('backups.remote_enabled', false);
        Settings::set('backups.checksum_enabled', true);
        Settings::set('backups.retention_days', 30);

        $dbPath = database_path('database.sqlite');
        File::put($dbPath, 'test');

        $publicPath = storage_path('app/public');
        File::ensureDirectoryExists($publicPath);
        File::put($publicPath.'/sample.txt', 'media');

        $user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'create backups']);
        $user->givePermissionTo('create backups');

        $response = $this->actingAs($user)->post(route('backups.store'));

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        Storage::disk('local')->deleteDirectory('backups');
        File::delete($publicPath.'/sample.txt');
        File::delete($dbPath);
    }
}
