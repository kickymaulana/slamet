<?php

namespace App\Http\Controllers;

use App\Models\BalanceTransaction;
use App\Models\Outlet;
use App\Models\User;
use App\Models\UserBalance;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::with('roles', 'outlet', 'balances.outlet')
            ->orderBy('is_approved')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => ['admin', 'Petugas Kantin', 'User'],
            'outlets' => Outlet::orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function approve(User $user, Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,Petugas Kantin,User',
            'outlet_id' => ['nullable', 'required_if:role,Petugas Kantin', 'exists:outlets,id'],
        ]);

        $user->syncRoles([$validated['role']]);
        $user->update([
            'is_approved' => true,
            'requested_role' => $validated['role'],
            'outlet_id' => $validated['outlet_id'] ?? null,
        ]);

        return back()->with('flash', ['success' => "{$user->name} diaktifkan sebagai {$validated['role']}."]);
    }

    public function updateRole(User $user, Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,Petugas Kantin,User',
            'outlet_id' => ['nullable', 'required_if:role,Petugas Kantin', 'exists:outlets,id'],
        ]);

        $user->syncRoles([$validated['role']]);
        $user->update(['outlet_id' => $validated['outlet_id'] ?? null]);

        return back()->with('flash', ['success' => "Role {$user->name} diubah menjadi {$validated['role']}."]);
    }

    public function deactivate(User $user)
    {
        $user->update(['is_approved' => false]);

        return back()->with('flash', ['success' => "{$user->name} dinonaktifkan."]);
    }

    public function saldo(User $user)
    {
        $transactions = BalanceTransaction::with('outlet', 'kasir')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Users/Saldo', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'nik' => $user->nik,
                'balances' => $user->balances()->with('outlet:id,name')->get()
                    ->map(fn ($b) => ['outlet_id' => $b->outlet_id, 'name' => $b->outlet->name, 'balance' => $b->balance]),
            ],
            'transactions' => $transactions,
        ]);
    }
}
