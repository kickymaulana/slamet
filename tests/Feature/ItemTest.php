<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['is_approved' => true]);
        $user->assignRole('admin');

        return $user;
    }

    private function kasir(Outlet $outlet): User
    {
        $user = User::factory()->create(['is_approved' => true, 'outlet_id' => $outlet->id]);
        $user->assignRole('kasir');

        return $user;
    }

    private function makeItem(Outlet $outlet, string $name = 'Soto'): Item
    {
        $category = Category::create(['name' => 'Kategori-'.uniqid()]);

        return Item::create([
            'outlet_id' => $outlet->id,
            'category_id' => $category->id,
            'name' => $name,
            'price' => 9000,
            'stock' => 5,
        ]);
    }

    public function test_item_photo_uploaded_to_minio(): void
    {
        Storage::fake('minio');
        $outlet = Outlet::create(['name' => 'Kantin 1']);
        $category = Category::create(['name' => 'Makanan']);

        $response = $this->actingAs($this->admin())->post('/items', [
            'outlet_id' => $outlet->id,
            'category_id' => $category->id,
            'name' => 'Ayam Bakar',
            'price' => 10000,
            'stock' => 5,
            'photo' => UploadedFile::fake()->image('ayam.png'),
        ]);

        $response->assertRedirect();
        $item = Item::firstOrFail();
        $this->assertNotNull($item->photo);
        Storage::disk('minio')->assertExists($item->photo);
    }

    public function test_item_foto_route_streams_image(): void
    {
        Storage::fake('minio');
        $outlet = Outlet::create(['name' => 'Kantin 1']);
        $category = Category::create(['name' => 'Makanan']);
        $item = Item::create([
            'outlet_id' => $outlet->id,
            'category_id' => $category->id,
            'name' => 'Soto',
            'price' => 9000,
            'stock' => 5,
            'photo' => 'items/test.jpg',
        ]);
        Storage::disk('minio')->put('items/test.jpg', 'fakejpeg');

        $this->actingAs($this->admin())
            ->get(route('items.foto', $item))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_replacing_photo_deletes_old_file_from_minio(): void
    {
        Storage::fake('minio');
        $outlet = Outlet::create(['name' => 'Kantin 1']);
        $category = Category::create(['name' => 'Makanan']);
        $item = Item::create([
            'outlet_id' => $outlet->id,
            'category_id' => $category->id,
            'name' => 'Soto',
            'price' => 9000,
            'stock' => 5,
            'photo' => 'items/old.jpg',
        ]);
        Storage::disk('minio')->put('items/old.jpg', 'old');

        $this->actingAs($this->admin())->post("/items/{$item->id}", [
            '_method' => 'PUT',
            'outlet_id' => $outlet->id,
            'category_id' => $category->id,
            'name' => 'Soto',
            'price' => 9000,
            'stock' => 5,
            'photo' => UploadedFile::fake()->image('baru.png'),
        ])->assertRedirect();

        Storage::disk('minio')->assertMissing('items/old.jpg');
        $this->assertNotSame('items/old.jpg', $item->fresh()->photo);
        Storage::disk('minio')->assertExists($item->fresh()->photo);
    }

    public function test_deleting_item_removes_photo_from_minio(): void
    {
        Storage::fake('minio');
        $outlet = Outlet::create(['name' => 'Kantin 1']);
        $category = Category::create(['name' => 'Makanan']);
        $item = Item::create([
            'outlet_id' => $outlet->id,
            'category_id' => $category->id,
            'name' => 'Soto',
            'price' => 9000,
            'stock' => 5,
            'photo' => 'items/hapus.jpg',
        ]);
        Storage::disk('minio')->put('items/hapus.jpg', 'foto');

        $this->actingAs($this->admin())->delete("/items/{$item->id}")->assertRedirect();

        Storage::disk('minio')->assertMissing('items/hapus.jpg');
        $this->assertSoftDeleted('items', ['id' => $item->id]);
    }

    public function test_kasir_index_scoped_to_bound_outlet(): void
    {
        $outlet1 = Outlet::create(['name' => 'Kantin 1']);
        $outlet2 = Outlet::create(['name' => 'Kantin 2']);
        $this->makeItem($outlet1, 'Ayam Kantin1');
        $this->makeItem($outlet2, 'Ikan Kantin2');
        $kasir = $this->kasir($outlet1);

        $page = $this->actingAs($kasir)->get('/items')->assertOk();
        $page->assertSee('Ayam Kantin1');
        $page->assertDontSee('Ikan Kantin2');
    }

    public function test_kasir_store_forces_own_outlet(): void
    {
        $outlet1 = Outlet::create(['name' => 'Kantin 1']);
        $outlet2 = Outlet::create(['name' => 'Kantin 2']);
        $category = Category::create(['name' => 'Makanan']);
        $kasir = $this->kasir($outlet1);

        $this->actingAs($kasir)->post('/items', [
            'outlet_id' => $outlet2->id, // dicoba ke kantin lain → dipaksa kantin 1
            'category_id' => $category->id,
            'name' => 'Rendang',
            'price' => 12000,
            'stock' => 5,
        ])->assertRedirect();

        $this->assertDatabaseHas('items', ['name' => 'Rendang', 'outlet_id' => $outlet1->id]);
    }

    public function test_kasir_cannot_edit_or_delete_other_outlet_item(): void
    {
        $outlet1 = Outlet::create(['name' => 'Kantin 1']);
        $outlet2 = Outlet::create(['name' => 'Kantin 2']);
        $item = $this->makeItem($outlet2);
        $kasir = $this->kasir($outlet1);

        $this->actingAs($kasir)->get("/items/{$item->id}/edit")->assertForbidden();
        $this->actingAs($kasir)->put("/items/{$item->id}", [
            'category_id' => $item->category_id,
            'name' => 'Ubah',
            'price' => 1000,
            'stock' => 1,
        ])->assertForbidden();
        $this->actingAs($kasir)->delete("/items/{$item->id}")->assertForbidden();
        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }
}
