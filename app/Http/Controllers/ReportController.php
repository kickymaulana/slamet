<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Outlet;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $from = request('from', today()->startOfMonth()->toDateString());
        $to = request('to', today()->toDateString());
        $outletId = auth()->user()->outlet_id ?? (request('outlet') ? (int) request('outlet') : null);

        $scope = Order::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId));

        $orders = (clone $scope)->with('outlet', 'user')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total_orders' => (clone $scope)->count(),
            'total_paid' => (clone $scope)->where('status', 'paid')->sum('total_amount'),
            'total_pending' => (clone $scope)->where('status', 'pending')->count(),
        ];

        return Inertia::render('Reports/Index', [
            'orders' => $orders,
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
            'outlets' => Outlet::orderBy('id')->get(['id', 'name']),
            'outlet' => $outletId,
        ]);
    }
}
