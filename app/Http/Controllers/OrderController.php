<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\UserBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function catalog()
    {
        $outletId = (int) request('outlet', Outlet::where('is_active', true)->orderBy('id')->value('id') ?? 1);

        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['items' => function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId)
                    ->where('is_active', true)
                    ->where('stock_date', now()->toDateString())
                    ->where('stock', '>', 0)
                    ->orderBy('name');
            }])
            ->get()
            ->map(function ($category) {
                $category->items->each(fn ($item) => $item->setAttribute('photo_url', $item->photo ? route('items.foto', $item) : null));

                return $category;
            });

        return Inertia::render('Menu/Catalog', [
            'categories' => $categories,
            'outlets' => Outlet::orderBy('id')->get(['id', 'name']),
            'active_outlet' => $outletId,
        ]);
    }

    public function checkout()
    {
        return Inertia::render('Menu/Checkout');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.qty' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $orderItems = [];
            $total = 0;

            foreach ($validated['items'] as $line) {
                $item = Item::whereKey($line['item_id'])->lockForUpdate()->firstOrFail();

                if ($item->outlet_id !== $validated['outlet_id']) {
                    throw ValidationException::withMessages(['items' => "Menu {$item->name} bukan dari kantin tujuan."]);
                }
                if (! $item->availableToday()) {
                    throw ValidationException::withMessages(['items' => "Menu {$item->name} tidak tersedia hari ini."]);
                }
                if ($item->stock < $line['qty']) {
                    throw ValidationException::withMessages(['items' => "Stok {$item->name} tidak mencukupi (sisa {$item->stock})."]);
                }

                $item->decrement('stock', $line['qty']);

                $subtotal = $item->price * $line['qty'];
                $total += $subtotal;
                $orderItems[] = [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'price' => $item->price,
                    'qty' => $line['qty'],
                    'subtotal' => $subtotal,
                ];
            }

            if (UserBalance::balanceOf(auth()->id(), $validated['outlet_id']) < $total) {
                throw ValidationException::withMessages(['items' => 'Saldo tidak cukup.']);
            }

            $order = Order::create([
                'nota_code' => $this->generateNotaCode(),
                'user_id' => auth()->id(),
                'outlet_id' => $validated['outlet_id'],
                'total_amount' => $total,
                'status' => Order::STATUS_PENDING,
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->items()->createMany($orderItems);

            return $order;
        });

        return redirect()->route('orders.show', $order)
            ->with('flash', ['success' => 'Pesanan berhasil dibuat.']);
    }

    public function index()
    {
        $orders = Order::with('user', 'items', 'outlet')
            ->where('user_id', auth()->id())
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order)
    {
        $user = auth()->user();
        abort_unless($order->user_id === $user->id || $user->can('order.read') || $user->can('payment.manage'), 403);

        return Inertia::render('Orders/Show', [
            'order' => $order->load('user', 'outlet', 'paidBy', 'items.item'),
            'qr_text' => route('kasir.index', ['q' => $order->nota_code]),
        ]);
    }

    private function generateNotaCode(): string
    {
        $date = now()->format('Ymd');
        $count = Order::whereDate('created_at', now()->toDateString())->count();

        return 'SLM-'.$date.'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
