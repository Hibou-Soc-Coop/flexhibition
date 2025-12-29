<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['name' => 'Italiano', 'code' => 'it', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'English', 'code' => 'en', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Français', 'code' => 'fr', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('languages')->insert($languages);
    }
}
