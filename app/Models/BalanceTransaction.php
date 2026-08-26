<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'outlet_id', 'order_id', 'type', 'amount', 'kasir_id', 'note'])]
#[Hidden([])]
class BalanceTransaction extends Model
{
    public const TYPE_TOPUP = 'topup';

    public const TYPE_DEDUCTION = 'deduction';

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }
}
