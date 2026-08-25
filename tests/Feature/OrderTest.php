<?php

namespace Tests\Feature;

use App\Models\BalanceTransaction;
use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\User;
use App\Models\UserBalance;
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

    private function karyawan(Outlet $outlet, int $balance = 100000): User
    {
        $user = User::factory()->create(['is_approved' => true, 'nik' => 'NIK-'.uniqid()]);
        $user->assignRole('User');
        UserBalance::create(['user_id' => $user->id, 'outlet_id' => $outlet->id, 'balance' => $balance]);

        return $user;
    }

    private function kasir(Outlet $outlet, int $balance = 100000): User
    {
        $user = User::factory()->create(['is_approved' => true, 'outlet_id' => $outlet->id]);
        $user->assignRole('Petugas Kantin');
        UserBalance::create(['user_id' => $user->id, 'outlet_id' => $outlet->id, 'balance' => $balance]);

        return $user;
    }

    private function outlet(string $name = 'Kantin 1'): Outlet
    {
        return Outlet::create(['name' => $name]);
    }

    private function makeItem(Outlet $outlet, int $stock = 5, ?string $stockDate = null): Item
    {
        $category = Category::create(['name' => 'Test-'.uniqid()]);

        return Item::create([
            'outlet_id' => $outlet->id,
            'category_id' => $category->id,
            'name' => 'Ayam Goreng',
            'price' => 8000,
            'stock' => $stock,
            'stock_date' => $stockDate ?? now()->toDateString(),
        ]);
    }

    private function orderPayload(Item $item, int $qty, Outlet $outlet): array
    {
        return [
            'outlet_id' => $outlet->id,
            'items' => [['item_id' => $item->id, 'qty' => $qty]],
        ];
    }

    public function test_order_created_deducts_stock_and_calculates_total(): void
    {
        $outlet = $this->outlet();
        $item = $this->makeItem($outlet);

        $response = $this->actingAs($this->karyawan($outlet))->post('/orders', $this->orderPayload($item, 2, $outlet) + ['notes' => 'Tidak pedas']);

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
        $outlet = $this->outlet();
        $item = $this->makeItem($outlet, 100);
        $user = $this->karyawan($outlet);

        $this->actingAs($user)->post('/orders', $this->orderPayload($item, 1, $outlet));
        $this->actingAs($user)->post('/orders', $this->orderPayload($item, 1, $outlet));

        $codes = Order::orderBy('id')->pluck('nota_code');
        $this->assertCount(2, $codes->unique());
        $this->assertMatchesRegularExpression('/^SLM-\d{8}-\d{4}$/', $codes->first());
    }

    public function test_order_rejected_when_stock_insufficient(): void
    {
        $outlet = $this->outlet();
        $item = $this->makeItem($outlet, 2);

        $response = $this->actingAs($this->karyawan($outlet))->post('/orders', $this->orderPayload($item, 5, $outlet));

        $response->assertSessionHasErrors('items');
        $this->assertSame(2, $item->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_rejected_when_item_not_available_today(): void
    {
        $outlet = $this->outlet();
        $item = $this->makeItem($outlet, 5, now()->subDay()->toDateString());

        $response = $this->actingAs($this->karyawan($outlet))->post('/orders', $this->orderPayload($item, 1, $outlet));

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_rejected_when_item_from_another_outlet(): void
    {
        $outlet1 = $this->outlet('Kantin 1');
        $outlet2 = $this->outlet('Kantin 2');
        $item = $this->makeItem($outlet1);

        $response = $this->actingAs($this->karyawan($outlet2))->post('/orders', $this->orderPayload($item, 1, $outlet2));

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_rejected_when_balance_empty_in_that_outlet(): void
    {
        $outlet1 = $this->outlet('Kantin 1');
        $outlet2 = $this->outlet('Kantin 2');
        $item2 = $this->makeItem($outlet2);

        // User punya saldo di Kantin 1 tapi pesan di Kantin 2 → ditolak.
        $response = $this->actingAs($this->karyawan($outlet1))->post('/orders', $this->orderPayload($item2, 1, $outlet2));

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_kasir_confirms_payment(): void
    {
        $outlet = $this->outlet();
        $item = $this->makeItem($outlet);
        $karyawan = $this->karyawan($outlet);
        $kasir = $this->kasir($outlet);

        $this->actingAs($karyawan)->post('/orders', $this->orderPayload($item, 1, $outlet));
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
        $outlet = $this->outlet();
        $item = $this->makeItem($outlet);
        $karyawan = $this->karyawan($outlet);
        $kasir = $this->kasir($outlet);

        $this->actingAs($karyawan)->post('/orders', $this->orderPayload($item, 1, $outlet));
        $order = Order::firstOrFail();
        $kasirUrl = '/kasir?q='.urlencode($order->nota_code).'&outlet='.$outlet->id.'&status=';

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
        $outlet = $this->outlet();
        $item = $this->makeItem($outlet);

        $this->actingAs($this->kasir($outlet))->post('/orders', $this->orderPayload($item, 1, $outlet))->assertForbidden();
    }

    public function test_order_rejected_when_balance_insufficient(): void
    {
        $outlet = $this->outlet();
        $item = $this->makeItem($outlet);

        $response = $this->actingAs($this->karyawan($outlet, 5000))->post('/orders', $this->orderPayload($item, 2, $outlet));

        $response->assertSessionHasErrors('items');
        $this->assertSame(5, $item->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_kasir_pay_deducts_balance_from_outlet(): void
    {
        $outlet = $this->outlet();
        $item = $this->makeItem($outlet);
        $karyawan = $this->karyawan($outlet);
        $kasir = $this->kasir($outlet);

        $this->actingAs($karyawan)->post('/orders', $this->orderPayload($item, 1, $outlet));
        $order = Order::firstOrFail();

        $this->actingAs($kasir)->post("/kasir/{$order->id}/pay");

        $this->assertSame(100000 - $order->total_amount, UserBalance::balanceOf($karyawan->id, $outlet->id));
        $this->assertDatabaseHas('balance_transactions', [
            'user_id' => $karyawan->id,
            'outlet_id' => $outlet->id,
            'type' => BalanceTransaction::TYPE_DEDUCTION,
            'amount' => $order->total_amount,
        ]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_PAID]);
    }

    public function test_kasir_pay_rejected_when_balance_insufficient(): void
    {
        $outlet = $this->outlet();
        $item = $this->makeItem($outlet);
        $karyawan = $this->karyawan($outlet);
        $kasir = $this->kasir($outlet);

        $this->actingAs($karyawan)->post('/orders', $this->orderPayload($item, 1, $outlet));
        $order = Order::firstOrFail();

        // Saldo turun setelah order dibuat.
        UserBalance::where('user_id', $karyawan->id)->where('outlet_id', $outlet->id)->update(['balance' => 5000]);

        $this->actingAs($kasir)->post("/kasir/{$order->id}/pay")->assertStatus(422);

        $this->assertSame(5000, UserBalance::balanceOf($karyawan->id, $outlet->id));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_PENDING]);
    }

    public function test_kasir_index_scoped_to_bound_outlet(): void
    {
        $outlet1 = $this->outlet('Kantin 1');
        $outlet2 = $this->outlet('Kantin 2');
        $item1 = $this->makeItem($outlet1);
        $item2 = $this->makeItem($outlet2);
        $kasir1 = $this->kasir($outlet1);

        $this->actingAs($this->karyawan($outlet1))->post('/orders', $this->orderPayload($item1, 1, $outlet1));
        $this->actingAs($this->karyawan($outlet2))->post('/orders', $this->orderPayload($item2, 1, $outlet2));

        $page = $this->actingAs($kasir1)->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => Inertia::getVersion()])
            ->get('/kasir')
            ->assertOk();

        $data = json_decode($page->getContent(), true)['props']['orders']['data'];
        $this->assertCount(1, $data);
        $this->assertSame($outlet1->id, $data[0]['outlet_id']);
    }

    public function test_kasir_pay_blocked_for_other_outlet(): void
    {
        $outlet1 = $this->outlet('Kantin 1');
        $outlet2 = $this->outlet('Kantin 2');
        $item2 = $this->makeItem($outlet2);
        $kasir1 = $this->kasir($outlet1);

        $this->actingAs($this->karyawan($outlet2))->post('/orders', $this->orderPayload($item2, 1, $outlet2));
        $order = Order::firstOrFail();

        $this->actingAs($kasir1)->post("/kasir/{$order->id}/pay")->assertForbidden();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_PENDING]);
    }

    public function test_topup_credits_balance_and_records_transaction(): void
    {
        $outlet = $this->outlet();
        $karyawan = $this->karyawan($outlet, 0);
        $kasir = $this->kasir($outlet);

        $this->actingAs($kasir)->post('/kasir/topup', [
            'nik' => $karyawan->nik,
            'amount' => 25000,
            'note' => 'Isi tunai',
        ])->assertSessionHasNoErrors();

        $this->assertSame(25000, UserBalance::balanceOf($karyawan->id, $outlet->id));
        $this->assertDatabaseHas('balance_transactions', [
            'user_id' => $karyawan->id,
            'outlet_id' => $outlet->id,
            'type' => BalanceTransaction::TYPE_TOPUP,
            'amount' => 25000,
            'kasir_id' => $kasir->id,
        ]);
    }

    public function test_bound_kasir_cannot_topup_other_outlet(): void
    {
        $outlet1 = $this->outlet('Kantin 1');
        $outlet2 = $this->outlet('Kantin 2');
        $karyawan = $this->karyawan($outlet2, 0);
        $kasir1 = $this->kasir($outlet1);

        $this->actingAs($kasir1)->post('/kasir/topup', [
            'nik' => $karyawan->nik,
            'amount' => 10000,
        ]);

        // Kasir Kantin 1 mengisi saldo di Kantin 1 (forced), bukan kantin 2.
        $this->assertSame(0, UserBalance::balanceOf($karyawan->id, $outlet2->id));
        $this->assertSame(10000, UserBalance::balanceOf($karyawan->id, $outlet1->id));
        $this->assertDatabaseHas('balance_transactions', [
            'user_id' => $karyawan->id,
            'outlet_id' => $outlet1->id,
            'type' => BalanceTransaction::TYPE_TOPUP,
        ]);
    }

    public function test_transfer_credits_receiver_and_records_both(): void
    {
        $outlet = $this->outlet();
        $sender = $this->karyawan($outlet, 50000);
        $receiver = $this->karyawan($outlet, 0);

        $this->actingAs($sender)->post('/saldo/transfer', [
            'outlet_id' => $outlet->id,
            'nik' => $receiver->nik,
            'amount' => 20000,
            'note' => 'jual saldo',
        ])->assertSessionHasNoErrors();

        $this->assertSame(30000, UserBalance::balanceOf($sender->id, $outlet->id));
        $this->assertSame(20000, UserBalance::balanceOf($receiver->id, $outlet->id));
        $this->assertDatabaseHas('balance_transactions', [
            'user_id' => $sender->id, 'type' => BalanceTransaction::TYPE_DEDUCTION, 'amount' => 20000,
        ]);
        $this->assertDatabaseHas('balance_transactions', [
            'user_id' => $receiver->id, 'type' => BalanceTransaction::TYPE_TOPUP, 'amount' => 20000,
        ]);
    }

    public function test_transfer_rejected_when_insufficient_or_self(): void
    {
        $outlet = $this->outlet();
        $sender = $this->karyawan($outlet, 5000);
        $receiver = $this->karyawan($outlet, 0);

        $this->actingAs($sender)->post('/saldo/transfer', [
            'outlet_id' => $outlet->id,
            'nik' => $receiver->nik,
            'amount' => 10000,
        ])->assertSessionHasErrors('amount');

        $this->actingAs($sender)->post('/saldo/transfer', [
            'outlet_id' => $outlet->id,
            'nik' => $sender->nik,
            'amount' => 1000,
        ])->assertSessionHasErrors('nik');

        $this->assertSame(5000, UserBalance::balanceOf($sender->id, $outlet->id));
        $this->assertSame(0, UserBalance::balanceOf($receiver->id, $outlet->id));
    }
}
