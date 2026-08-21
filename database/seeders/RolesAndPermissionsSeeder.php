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

        $kasir = Role::firstOrCreate(['name' => 'kasir']);
        $kasir->syncPermissions(['order.read', 'payment.manage']);

        $karyawan = Role::firstOrCreate(['name' => 'karyawan']);
        $karyawan->syncPermissions(['order.create', 'order.read']);
    }
}
