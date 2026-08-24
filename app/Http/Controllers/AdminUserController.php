<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::with('roles', 'outlet')
            ->orderBy('is_approved')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => ['admin', 'kasir', 'karyawan'],
            'outlets' => Outlet::orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function approve(User $user, Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,kasir,karyawan',
            'outlet_id' => ['nullable', 'required_if:role,kasir', 'exists:outlets,id'],
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
            'role' => 'required|in:admin,kasir,karyawan',
            'outlet_id' => ['nullable', 'required_if:role,kasir', 'exists:outlets,id'],
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
}
