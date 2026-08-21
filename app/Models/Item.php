<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['outlet_id', 'category_id', 'name', 'description', 'price', 'photo', 'stock', 'stock_date', 'is_active'])]
#[Hidden([])]
class Item extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'stock_date' => 'date:Y-m-d',
            'is_active' => 'boolean',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function availableToday(): bool
    {
        return $this->is_active
            && $this->stock_date?->format('Y-m-d') === today()->toDateString()
            && $this->stock > 0;
    }
}
