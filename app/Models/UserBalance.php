<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'outlet_id', 'balance'])]
#[Hidden([])]
class UserBalance extends Model
{
    protected function casts(): array
    {
        return [
            'balance' => 'integer',
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

    public static function credit(int $userId, int $outletId, int $amount): void
    {
        static::firstOrCreate(
            ['user_id' => $userId, 'outlet_id' => $outletId],
            ['balance' => 0],
        )->increment('balance', $amount);
    }

    public static function debit(int $userId, int $outletId, int $amount): void
    {
        static::where('user_id', $userId)->where('outlet_id', $outletId)
            ->lockForUpdate()
            ->firstOrFail()
            ->decrement('balance', $amount);
    }

    public static function balanceOf(int $userId, int $outletId): int
    {
        return (int) static::where('user_id', $userId)->where('outlet_id', $outletId)->value('balance');
    }
}
