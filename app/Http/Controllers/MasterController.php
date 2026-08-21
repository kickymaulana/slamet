<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterController extends Controller
{
    private array $entities = [
        'categories' => [
            'model' => Category::class,
            'label' => 'Kategori',
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'sort_order', 'label' => 'Urutan'],
                ['key' => 'is_active', 'label' => 'Aktif'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Kategori', 'type' => 'text'],
                ['key' => 'sort_order', 'label' => 'Urutan Tampil', 'type' => 'number'],
                ['key' => 'is_active', 'label' => 'Aktif', 'type' => 'switch'],
            ],
            'rules' => [
                'name' => 'required|string|max:255',
                'sort_order' => 'required|integer|min:0',
                'is_active' => 'boolean',
            ],
        ],
    ];

    public function index(string $entity)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $items = $config['model']::query()->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Masters/Index', [
            'entity' => $entity,
            'config' => $config,
            'items' => $items,
            'options' => $this->options($entity),
        ]);
    }

    public function create(string $entity)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];

        return Inertia::render('Masters/Form', [
            'entity' => $entity,
            'config' => $config,
            'item' => null,
            'options' => $this->options($entity),
        ]);
    }

    public function edit(string $entity, int $id)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $item = $config['model']::findOrFail($id);

        return Inertia::render('Masters/Form', [
            'entity' => $entity,
            'config' => $config,
            'item' => $item,
            'options' => $this->options($entity),
        ]);
    }

    public function store(Request $request, string $entity)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $data = $request->validate($config['rules']);

        $config['model']::create($data);

        return redirect()->route('masters.index', $entity)
            ->with('flash', ['success' => "{$config['label']} berhasil ditambahkan."]);
    }

    public function update(Request $request, string $entity, int $id)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $data = $request->validate($config['rules']);

        $config['model']::findOrFail($id)->update($data);

        return redirect()->route('masters.index', $entity)
            ->with('flash', ['success' => "{$config['label']} berhasil diperbarui."]);
    }

    public function destroy(string $entity, int $id)
    {
        abort_unless(isset($this->entities[$entity]), 404);

        $config = $this->entities[$entity];
        $item = $config['model']::withCount('items')->findOrFail($id);
        if ($item->items_count > 0) {
            return redirect()->route('masters.index', $entity)
                ->with('flash', ['error' => 'Kategori masih dipakai menu, tidak bisa dihapus.']);
        }
        $item->delete();

        return redirect()->route('masters.index', $entity)
            ->with('flash', ['success' => "{$config['label']} berhasil dihapus."]);
    }

    private function options(string $entity): array
    {
        return [];
    }
}
