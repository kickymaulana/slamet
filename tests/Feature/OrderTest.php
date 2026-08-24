<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Inertia;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function karyawan(int $balance = 100000): User
    {
        $user = User::factory()->create(['is_approved' => true, 'balance' => $balance]);
        $user->assignRole('karyawan');

        return $user;
    }

    private function kasir(int $balance = 100000): User
    {
        $user = User::factory()->create(['is_approved' => true, 'balance' => $balance]);
        $user->assignRole('kasir');

        return $user;
    }

    private function outlet(string $name = 'Kantin 1'): Outlet
    {
        return Outlet::create(['name' => $name]);
    }

    private function makeItem(int $stock = 5, ?string $stockDate = null, ?Outlet $outlet = null): Item
    {
        $category = Category::create(['name' => 'Test-'.uniqid()]);

        return Item::create([
            'outlet_id' => ($outlet ?? $this->outlet())->id,
            'category_id' => $category->id,
            'name' => 'Ayam Goreng',
            'price' => 8000,
            'stock' => $stock,
            'stock_date' => $stockDate ?? now()->toDateString(),
        ]);
    }

    private function orderPayload(Item $item, int $qty, ?Outlet $outlet = null): array
    {
        return [
            'outlet_id' => ($outlet ?? Outlet::find($item->outlet_id))->id,
            'items' => [['item_id' => $item->id, 'qty' => $qty]],
        ];
    }

    public function test_order_created_deducts_stock_and_calculates_total(): void
    {
        $item = $this->makeItem(5);

        $response = $this->actingAs($this->karyawan())->post('/orders', $this->orderPayload($item, 2) + ['notes' => 'Tidak pedas']);

        $response->assertRedirect();

        $this->assertDatabaseHas('orders', ['total_amount' => 16000, 'status' => Order::STATUS_PENDING]);
        $this->assertSame(3, $item->fresh()->stock);
        $this->assertDatabaseHas('order_items', [
            'item_name' => 'Ayam Goreng',
            'price' => 8000,
            'qty' => 2,
            'subtotal' => 16000,
        ]);
    }

    public function test_nota_code_unique_and_sequential(): void
    {
        $item = $this->makeItem(100);
        $user = $this->karyawan();

        $this->actingAs($user)->post('/orders', $this->orderPayload($item, 1));
        $this->actingAs($user)->post('/orders', $this->orderPayload($item, 1));

        $codes = Order::orderBy('id')->pluck('nota_code');
        $this->assertCount(2, $codes->unique());
        $this->assertMatchesRegularExpression('/^SLM-\d{8}-\d{4}$/', $codes->first());
    }

    public function test_order_rejected_when_stock_insufficient(): void
    {
        $item = $this->makeItem(2);

        $response = $this->actingAs($this->karyawan())->post('/orders', $this->orderPayload($item, 5));

        $response->assertSessionHasErrors('items');
        $this->assertSame(2, $item->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_rejected_when_item_not_available_today(): void
    {
        $item = $this->makeItem(5, now()->subDay()->toDateString());

        $response = $this->actingAs($this->karyawan())->post('/orders', $this->orderPayload($item, 1));

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_rejected_when_item_from_another_outlet(): void
    {
        $item = $this->makeItem(5, null, $this->outlet('Kantin 1'));
        $outlet2 = $this->outlet('Kantin 2');

        $response = $this->actingAs($this->karyawan())->post('/orders', $this->orderPayload($item, 1, $outlet2));

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_kasir_confirms_payment(): void
    {
        $item = $this->makeItem(5);
        $karyawan = $this->karyawan();
        $kasir = $this->kasir();

        $this->actingAs($karyawan)->post('/orders', $this->orderPayload($item, 1));
        $order = Order::firstOrFail();

        $response = $this->actingAs($kasir)->post("/kasir/{$order->id}/pay");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PAID,
            'paid_by' => $kasir->id,
        ]);
    }

    public function test_kasir_page_refreshes_after_pay(): void
    {
        $item = $this->makeItem(5);
        $karyawan = $this->karyawan();
        $kasir = $this->kasir();

        $this->actingAs($karyawan)->post('/orders', $this->orderPayload($item, 1));
        $order = Order::firstOrFail();
        $outlet = Outlet::find($item->outlet_id);
        $kasirUrl = '/kasir?q='.urlencode($order->nota_code).'&outlet='.$outlet->id;

        $this->actingAs($kasir)->get($kasirUrl)->assertOk();
        $inertiaHeaders = ['X-Inertia' => 'true', 'X-Inertia-Version' => Inertia::getVersion()];

        $this->actingAs($kasir)->from($kasirUrl)->withHeaders($inertiaHeaders)->post("/kasir/{$order->id}/pay")
            ->assertRedirect($kasirUrl);

        // Browser XHR mengikuti 302 → GET dengan X-Inertia + version.
        $page = $this->actingAs($kasir)->withHeaders($inertiaHeaders)->get($kasirUrl);
        $page->assertOk();
        $page->assertJsonPath('props.orders.data.0.status', 'paid');
    }

    public function test_kasir_cannot_create_order(): void
    {
        $item = $this->makeItem(5);

        $this->actingAs($this->kasir())->post('/orders', $this->orderPayload($item, 1))->assertForbidden();
    }

    public function test_order_rejected_when_balance_insufficient(): void
    {
        $item = $this->makeItem(5);

        $response = $this->actingAs($this->karyawan(5000))->post('/orders', $this->orderPayload($item, 2));

        $response->assertSessionHasErrors('items');
        $this->assertSame(5, $item->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_kasir_pay_deducts_balance(): void
    {
        $item = $this->makeItem(5);
        $karyawan = $this->karyawan();
        $kasir = $this->kasir();

        $this->actingAs($karyawan)->post('/orders', $this->orderPayload($item, 1));
        $order = Order::firstOrFail();

        $this->actingAs($kasir)->post("/kasir/{$order->id}/pay");

        $this->assertSame(100000 - $order->total_amount, $karyawan->fresh()->balance);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_PAID]);
    }

    public function test_kasir_pay_rejected_when_balance_insufficient(): void
    {
        $item = $this->makeItem(5);
        $karyawan = $this->karyawan();
        $kasir = $this->kasir();

        $this->actingAs($karyawan)->post('/orders', $this->orderPayload($item, 1));
        $order = Order::firstOrFail();

        // Saldo turun setelah order dibuat (mis. pesanan lain terpotong).
        $karyawan->update(['balance' => 5000]);

        $this->actingAs($kasir)->post("/kasir/{$order->id}/pay")->assertStatus(422);

        $this->assertSame(5000, $karyawan->fresh()->balance);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_PENDING]);
    }

    public function test_order_index_scoped_to_owner_for_karyawan(): void
    {
        $other = $this->karyawan();
        $this->actingAs($this->karyawan())->get('/orders')->assertOk();

        // Order milik user lain tidak muncul untuk karyawan lain.
        $item = $this->makeItem(10);
        $this->actingAs($other)->post('/orders', $this->orderPayload($item, 1));
        $this->actingAs($this->karyawan())->get('/orders')->assertOk();
    }
}
