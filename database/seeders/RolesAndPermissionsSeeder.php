<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'category.create', 'category.read', 'category.update', 'category.delete',
            'item.create', 'item.read', 'item.update', 'item.delete',
            'stock.manage',
            'order.create', 'order.read',
            'payment.manage',
            'report.read',
            'user.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        $petugas = Role::firstOrCreate(['name' => 'Petugas Kantin']);
        $petugas->syncPermissions([
            'order.read', 'payment.manage',
            'item.read', 'item.create', 'item.update', 'item.delete',
            'stock.manage',
            'category.read', 'category.create', 'category.update', 'category.delete',
            'report.read',
        ]);

        $user = Role::firstOrCreate(['name' => 'User']);
        $user->syncPermissions(['order.create', 'order.read']);
    }
}
