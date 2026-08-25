<?php

namespace App\Http\Controllers;

use App\Models\BalanceTransaction;
use App\Models\Outlet;
use App\Models\User;
use App\Models\UserBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SaldoController extends Controller
{
    public function riwayat()
    {
        $transactions = BalanceTransaction::with('outlet', 'kasir')
            ->where('user_id', auth()->id())
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Saldo/Riwayat', [
            'transactions' => $transactions,
        ]);
    }

    public function transfer()
    {
        return Inertia::render('Saldo/Transfer', [
            'outlets' => Outlet::orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function transferSubmit(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'nik' => 'required|exists:users,nik',
            'amount' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        $sender = auth()->user();
        $receiver = User::where('nik', $validated['nik'])->firstOrFail();

        if ($receiver->id === $sender->id) {
            throw ValidationException::withMessages(['nik' => 'Tidak bisa transfer ke diri sendiri.']);
        }

        if (UserBalance::balanceOf($sender->id, $validated['outlet_id']) < $validated['amount']) {
            throw ValidationException::withMessages(['amount' => 'Saldo tidak cukup.']);
        }

        DB::transaction(function () use ($sender, $receiver, $validated) {
            UserBalance::debit($sender->id, $validated['outlet_id'], $validated['amount']);
            UserBalance::credit($receiver->id, $validated['outlet_id'], $validated['amount']);

            BalanceTransaction::create([
                'user_id' => $sender->id,
                'outlet_id' => $validated['outlet_id'],
                'type' => BalanceTransaction::TYPE_DEDUCTION,
                'amount' => $validated['amount'],
                'note' => "Transfer ke {$receiver->name}",
            ]);
            BalanceTransaction::create([
                'user_id' => $receiver->id,
                'outlet_id' => $validated['outlet_id'],
                'type' => BalanceTransaction::TYPE_TOPUP,
                'amount' => $validated['amount'],
                'note' => "Transfer dari {$sender->name}",
            ]);
        });

        return redirect()->route('saldo.riwayat')
            ->with('flash', ['success' => "Transfer {$validated['amount']} Coin ke {$receiver->name} berhasil."]);
    }
}
