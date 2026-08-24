<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\Outlet;
use App\Models\User;
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
            'balance' => 100000,
        ]);
        $admin->assignRole('admin');

        $kasir1 = User::create([
            'name' => 'Dedi Maulana',
            'email' => 'kasir@slamet.test',
            'password' => 'password',
            'nik' => 'K190327',
            'is_approved' => true,
            'balance' => 100000,
            'outlet_id' => $outlets[0]->id, // Kantin 1
        ]);
        $kasir1->assignRole('kasir');

        $kasir2 = User::create([
            'name' => 'Yildiz Zulhamdy',
            'email' => 'kasir2@slamet.test',
            'password' => 'password',
            'nik' => 'D240728',
            'is_approved' => true,
            'balance' => 100000,
            'outlet_id' => $outlets[1]->id, // Kantin 2
        ]);
        $kasir2->assignRole('kasir');

        $karyawan = User::create([
            'name' => 'Elfrina',
            'email' => 'karyawan@slamet.test',
            'password' => 'password',
            'nik' => 'K190798',
            'is_approved' => true,
            'balance' => 100000,
        ]);
        $karyawan->assignRole('karyawan');

        $categories = collect([
            ['name' => 'Nasi', 'sort_order' => 1],
            ['name' => 'Makanan', 'sort_order' => 2],
            ['name' => 'Minuman', 'sort_order' => 3],
            ['name' => 'Snack', 'sort_order' => 4],
        ])->map(fn ($c) => Category::create($c));

        $today = now()->toDateString();
        $seedItems = [
            ['Nasi', 'Tanpa Nasi', 0, 200],
            ['Nasi', 'Nasi 1 Centong', 0, 150],
            ['Nasi', 'Nasi 1.5 Centong', 0, 100],
            ['Nasi', 'Nasi 2 Centong', 0, 80],
            ['Makanan', 'Ayam Goreng', 8000, 40],
            ['Makanan', 'Ayam Bakar', 10000, 30],
            ['Makanan', 'Tempe Goreng', 1500, 60],
            ['Makanan', 'Sayur Sop', 3000, 50],
            ['Minuman', 'Es Teh', 2000, 70],
            ['Minuman', 'Kopi Hitam', 3000, 60],
            ['Minuman', 'Air Mineral', 3000, 80],
            ['Snack', 'Pisang Goreng', 2000, 50],
            ['Snack', 'Roti Bakar', 5000, 30],
        ];

        foreach ($seedItems as [$cat, $name, $price, $stock]) {
            Item::create([
                'outlet_id' => $outlets->first()->id,
                'category_id' => $categories->firstWhere('name', $cat)->id,
                'name' => $name,
                'price' => $price,
                'stock' => $stock,
                'stock_date' => $today,
            ]);
        }

        foreach ([
            ['Makanan', 'Gado-Gado', 8000, 25],
            ['Makanan', 'Soto Ayam', 9000, 20],
            ['Minuman', 'Jus Alpukat', 7000, 15],
            ['Snack', 'Gorengan', 1000, 80],
        ] as [$cat, $name, $price, $stock]) {
            Item::create([
                'outlet_id' => $outlets->last()->id,
                'category_id' => $categories->firstWhere('name', $cat)->id,
                'name' => $name,
                'price' => $price,
                'stock' => $stock,
                'stock_date' => $today,
            ]);
        }
    }
}
