<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Ramsey\Collection\Set;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->withoutTwoFactor()->create([
            'name' => 'AdminOwl',
            'email' => 'digital@hiboucoop.org',
            'password' => bcrypt('gatti-compreso-leoni'),
        ]);

        $this->call([
            LanguageSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
