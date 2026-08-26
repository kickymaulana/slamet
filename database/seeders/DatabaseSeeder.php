<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Outlet;
use App\Models\User;
use App\Models\UserBalance;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $outlets = collect([
            ['name' => 'Kantin 1'],
            ['name' => 'Kantin 2'],
        ])->map(fn ($o) => Outlet::create($o));

        $admin = User::create([
            'name' => 'Kicky Maulana',
            'email' => 'admin@slamet.test',
            'password' => 'password',
            'nik' => 'D260065',
            'is_approved' => true,
        ]);
        $admin->assignRole('admin');

        $kasir1 = User::create([
            'name' => 'Dedi Maulana',
            'email' => 'kasir@slamet.test',
            'password' => 'password123',
            'nik' => 'K190327',
            'is_approved' => true,
            'outlet_id' => $outlets[0]->id, // Kantin 1
        ]);
        $kasir1->assignRole('Petugas Kantin');

        $kasir2 = User::create([
            'name' => 'Yildiz Zulhamdy',
            'email' => 'kasir2@slamet.test',
            'password' => 'password123',
            'nik' => 'D240728',
            'is_approved' => true,
            'outlet_id' => $outlets[1]->id, // Kantin 2
        ]);
        $kasir2->assignRole('Petugas Kantin');

        $karyawan = User::create([
            'name' => 'Elfrina',
            'email' => 'karyawan@slamet.test',
            'password' => 'password123',
            'nik' => 'K190798',
            'is_approved' => true,
        ]);
        $karyawan->assignRole('User');

        // Saldo awal: Kantin 1 = 100.000, Kantin 2 = 0
        foreach ([$admin, $kasir1, $kasir2, $karyawan] as $u) {
            UserBalance::create(['user_id' => $u->id, 'outlet_id' => $outlets[0]->id, 'balance' => 100000]);
        }

        $categories = collect([
            ['name' => 'Nasi', 'sort_order' => 1],
            ['name' => 'Makanan', 'sort_order' => 2],
            ['name' => 'Minuman', 'sort_order' => 3],
            ['name' => 'Snack', 'sort_order' => 4],
        ])->map(fn ($c) => Category::create($c));
    }
}
