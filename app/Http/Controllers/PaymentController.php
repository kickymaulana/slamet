<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index()
    {
        $outletId = (int) request('outlet', Outlet::where('is_active', true)->orderBy('id')->value('id') ?? 1);
        $query = Order::with('user', 'items')->where('outlet_id', $outletId);

        if ($q = request('q')) {
            $query->where('nota_code', 'like', "%{$q}%");
        } else {
            $query->whereDate('created_at', today())
                ->orderByRaw("FIELD(status, 'pending', 'paid', 'cancelled')")
                ->orderBy('created_at', 'desc');
        }

        $orders = $query->paginate(20)->withQueryString();

        return Inertia::render('Kasir/Payment', [
            'orders' => $orders,
            'query' => request('q', ''),
            'outlets' => Outlet::orderBy('id')->get(['id', 'name']),
            'outlet' => $outletId,
        ]);
    }

    public function pay(Order $order, Request $request)
    {
        DB::transaction(function () use ($order) {
            $order->lockForUpdate();
            abort_if($order->status !== Order::STATUS_PENDING, 422);

            $user = $order->user()->lockForUpdate()->firstOrFail();
            abort_if($user->balance < $order->total_amount, 422, 'Saldo tidak cukup.');

            $user->decrement('balance', $order->total_amount);
            $order->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
                'paid_by' => auth()->id(),
            ]);
        });

        $referer = $request->headers->get('referer');
        $q = $referer ? parse_url($referer, PHP_URL_QUERY) : null;
        parse_str((string) $q, $params);

        return redirect()->route('kasir.index', ['q' => $params['q'] ?? '', 'outlet' => $params['outlet'] ?? $order->outlet_id])
            ->with('flash', ['success' => "Pesanan {$order->nota_code} lunas."]);
    }
}
