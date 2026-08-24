<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockController extends Controller
{
    public function today()
    {
        $user = auth()->user();
        $outletId = $user->outlet_id ?? (int) request('outlet', Outlet::where('is_active', true)->orderBy('id')->value('id') ?? 1);

        $items = Item::with('category')
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderBy('category_id')
            ->orderBy('name')
            ->get(['id', 'name', 'stock', 'stock_date', 'category_id', 'price']);

        return Inertia::render('Stock/Today', [
            'items' => $items,
            'today' => now()->toDateString(),
            'outlets' => Outlet::orderBy('id')->get(['id', 'name']),
            'outlet' => $outletId,
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:items,id',
            'items.*.stock' => 'required|integer|min:0',
        ]);

        $user = auth()->user();
        if ($user->outlet_id) {
            $foreign = Item::whereIn('id', collect($validated['items'])->pluck('id'))
                ->where('outlet_id', '!=', $user->outlet_id)
                ->exists();
            abort_if($foreign, 403, 'Bukan menu kantin Anda.');
        }

        foreach ($validated['items'] as $entry) {
            Item::whereKey($entry['id'])->update([
                'stock' => $entry['stock'],
                'stock_date' => now()->toDateString(),
            ]);
        }

        return back()->with('flash', ['success' => 'Stok hari ini berhasil disimpan.']);
    }
}
