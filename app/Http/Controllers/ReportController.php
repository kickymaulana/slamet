<?php

namespace App\Http\Controllers;

use App\Models\BalanceTransaction;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Outlet;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $from = request('from', today()->startOfMonth()->toDateString());
        $to = request('to', today()->toDateString());
        $outletId = auth()->user()->outlet_id ?? (request('outlet') ? (int) request('outlet') : null);
        $tab = request('tab', 'penjualan');

        $orderScope = Order::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId));

        $penjualan = null;
        if ($tab === 'penjualan') {
            $paidCount = (clone $orderScope)->where('status', Order::STATUS_PAID)->count();
            $paidSum = (clone $orderScope)->where('status', Order::STATUS_PAID)->sum('total_amount');

            $penjualan = [
                'summary' => [
                    'total_orders' => (clone $orderScope)->count(),
                    'total_paid' => $paidSum,
                    'total_pending' => (clone $orderScope)->where('status', Order::STATUS_PENDING)->count(),
                    'avg_per_order' => $paidCount > 0 ? intdiv($paidSum, $paidCount) : 0,
                ],
                'orders' => (clone $orderScope)->with('user', 'outlet')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20)
                    ->withQueryString(),
            ];
        }

        $menu = null;
        if ($tab === 'menu') {
            $menu = OrderItem::query()
                ->whereHas('order', function ($q) use ($from, $to, $outletId) {
                    $q->where('status', Order::STATUS_PAID)
                        ->whereDate('created_at', '>=', $from)
                        ->whereDate('created_at', '<=', $to)
                        ->when($outletId, fn ($o) => $o->where('outlet_id', $outletId));
                })
                ->selectRaw('item_name, SUM(qty) as total_qty, SUM(subtotal) as total_revenue')
                ->groupBy('item_name')
                ->orderByDesc('total_qty')
                ->get();
        }

        $topup = null;
        if ($tab === 'saldo') {
            $topupScope = BalanceTransaction::query()
                ->where('type', BalanceTransaction::TYPE_TOPUP)
                ->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to)
                ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId));

            $topup = [
                'summary' => [
                    'total' => (clone $topupScope)->sum('amount'),
                    'count' => (clone $topupScope)->count(),
                ],
                'transactions' => (clone $topupScope)->with('user', 'kasir', 'outlet')
                    ->latest()
                    ->limit(50)
                    ->get(),
            ];
        }

        $stocks = null;
        if ($tab === 'stok') {
            $stocks = Item::with('outlet')
                ->where('is_active', true)
                ->where('stock_date', today()->toDateString())
                ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
                ->orderBy('outlet_id')
                ->orderBy('name')
                ->get(['id', 'name', 'photo', 'price', 'stock', 'outlet_id']);
        }

        return Inertia::render('Reports/Index', [
            'tab' => $tab,
            'from' => $from,
            'to' => $to,
            'outlets' => Outlet::orderBy('id')->get(['id', 'name']),
            'outlet' => $outletId,
            'penjualan' => $penjualan,
            'menu' => $menu,
            'topup' => $topup,
            'stocks' => $stocks,
        ]);
    }
}
