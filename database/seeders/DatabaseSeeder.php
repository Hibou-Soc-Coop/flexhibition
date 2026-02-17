<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Ramsey\Collection\Set;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->withoutTwoFactor()->create([
            'name' => 'AdminOwl',
            'email' => 'digital@hiboucoop.org',
            'password' => bcrypt('gatti-compreso-leoni'),
        ]);
        $admin->assignRole('admin');

        $editor = User::factory()->withoutTwoFactor()->create([
            'name' => 'EditorOwl',
            'email' => 'test@hiboucoop.org',
            'password' => bcrypt('cani-compreso-leoni'),
        ]);
        $editor->assignRole('editor');

        $this->call([
            LanguageSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
