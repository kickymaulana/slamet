<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ItemController extends Controller
{
    private function storePhoto(Request $request): string
    {
        return Storage::disk('minio')->putFileAs(
            'items',
            $request->file('photo'),
            time().'-'.Str::random(8).'.jpg',
        );
    }

    public function foto(Item $item)
    {
        abort_if(empty($item->photo), 404);

        $disk = Storage::disk('minio');
        try {
            $content = $disk->get($item->photo);
        } catch (\Throwable $e) {
            abort(404);
        }

        return response($content, 200, [
            'Content-Type' => $disk->mimeType($item->photo) ?: 'image/jpeg',
        ]);
    }

    private function boundOutlet(): ?int
    {
        return auth()->user()->outlet_id;
    }

    private function assertOwnOutlet(Item $item): void
    {
        abort_if($this->boundOutlet() && $item->outlet_id !== $this->boundOutlet(), 403, 'Bukan menu kantin Anda.');
    }

    public function index()
    {
        $query = Item::with('category', 'outlet');

        if ($this->boundOutlet()) {
            $query->where('outlet_id', $this->boundOutlet());
        }

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $items = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Items/Index', [
            'items' => $items,
            'today' => now()->toDateString(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Items/Form', [
            'item' => null,
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'outlets' => Outlet::orderBy('id')->get(['id', 'name']),
            'bound_outlet' => $this->boundOutlet(),
        ]);
    }

    public function edit(Item $item)
    {
        $this->assertOwnOutlet($item);

        return Inertia::render('Items/Form', [
            'item' => $item->load('category'),
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'outlets' => Outlet::orderBy('id')->get(['id', 'name']),
            'bound_outlet' => $this->boundOutlet(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'outlet_id' => $this->boundOutlet() ? 'nullable' : 'required|exists:outlets,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data['outlet_id'] = $this->boundOutlet() ?? $data['outlet_id'];

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->storePhoto($request);
        }

        Item::create($data);

        return redirect()->route('items.index')
            ->with('flash', ['success' => 'Menu berhasil ditambahkan.']);
    }

    public function update(Request $request, Item $item)
    {
        $this->assertOwnOutlet($item);

        $data = $request->validate([
            'outlet_id' => $this->boundOutlet() ? 'nullable' : 'required|exists:outlets,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data['outlet_id'] = $this->boundOutlet() ?? $data['outlet_id'];

        if ($request->hasFile('photo')) {
            if ($item->photo) {
                Storage::disk('minio')->delete($item->photo);
            }
            $data['photo'] = $this->storePhoto($request);
        }

        $item->update($data);

        return redirect()->route('items.index')
            ->with('flash', ['success' => 'Menu berhasil diperbarui.']);
    }

    public function destroy(Item $item)
    {
        $this->assertOwnOutlet($item);

        if ($item->photo) {
            Storage::disk('minio')->delete($item->photo);
        }
        $item->delete();

        return redirect()->route('items.index')
            ->with('flash', ['success' => 'Menu berhasil dihapus.']);
    }
}
