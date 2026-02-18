<?php

namespace App\Providers;

use App\Services\SettingsService;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('settings', function ($app) {
            return new SettingsService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Storage::extend('google', function ($app, $config) {
            $client = new GoogleClient;
            $client->setClientId($config['clientId'] ?? '');
            $client->setClientSecret($config['clientSecret'] ?? '');
            $client->refreshToken($config['refreshToken'] ?? '');
            $client->setApplicationName(config('app.name'));

            $service = new GoogleDrive($client);
            $options = array_filter([
                'teamDriveId' => $config['teamDriveId'] ?? null,
                'sharedFolderId' => $config['sharedFolderId'] ?? null,
            ]);

            $adapter = new GoogleDriveAdapter(
                $service,
                $config['folder'] ?? '',
                $options
            );

            $filesystem = new Filesystem($adapter);

            return new FilesystemAdapter($filesystem, $adapter, $config);
        });
    }
}
