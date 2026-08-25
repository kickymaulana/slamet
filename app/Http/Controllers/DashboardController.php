<?php

namespace App\Http\Controllers;

use App\Models\BalanceTransaction;
use App\Models\Item;
use App\Models\Order;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today()->toDateString();
        $user = auth()->user();

        $stats = [
            'today_orders' => Order::whereDate('created_at', $today)->count(),
            'today_revenue' => Order::whereDate('created_at', $today)->where('status', 'paid')->sum('total_amount'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'low_stock' => Item::whereDate('stock_date', $today)->where('stock', '<', 5)->count(),
        ];

        $recentOrders = Order::with('user', 'outlet')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get(['id', 'nota_code', 'user_id', 'outlet_id', 'total_amount', 'status', 'created_at']);

        $balanceTransactions = BalanceTransaction::with('outlet', 'kasir')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'balanceTransactions' => $balanceTransactions,
        ]);
    }
}
