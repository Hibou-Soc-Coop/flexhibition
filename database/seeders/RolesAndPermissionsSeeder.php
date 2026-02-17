<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Resetta la cache dei ruoli e permessi
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crea i permessi
        Permission::firstOrCreate(['name' => 'manage backups']);
        Permission::firstOrCreate(['name' => 'create backups']);
        Permission::firstOrCreate(['name' => 'restore backups']);
        Permission::firstOrCreate(['name' => 'manage languages']);

        // Crea i ruoli e assegna i permessi esistenti
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'manage backups',
            'create backups',
            'restore backups',
            'manage languages'
        ]);

        $editorRole = Role::firstOrCreate(['name' => 'editor']);
        $editorRole->givePermissionTo([
            'manage backups',
            'create backups'
        ]);
    }
}