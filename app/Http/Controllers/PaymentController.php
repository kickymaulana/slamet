<?php

namespace App\Http\Controllers;

use App\Models\BalanceTransaction;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\User;
use App\Models\UserBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentController extends Controller
{
    private function outletId(?Request $request = null): int
    {
        $user = auth()->user();
        if ($user->outlet_id) {
            return $user->outlet_id;
        }

        return (int) ($request ? $request->query('outlet') : request('outlet'))
            ?: Outlet::where('is_active', true)->orderBy('id')->value('id') ?? 1;
    }

    public function index()
    {
        $outletId = $this->outletId();
        $status = request('status');
        $query = Order::with('user', 'items')->where('outlet_id', $outletId);

        if ($q = request('q')) {
            $query->where('nota_code', 'like', "%{$q}%");
        } else {
            $query->whereDate('created_at', today())
                ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'paid' THEN 2 ELSE 3 END")
                ->orderBy('created_at', 'desc');
        }

        if ($status && in_array($status, Order::STATUSES)) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(20)->withQueryString();

        return Inertia::render('Kasir/Payment', [
            'orders' => $orders,
            'query' => request('q', ''),
            'status' => $status ?: '',
            'outlets' => Outlet::orderBy('id')->get(['id', 'name']),
            'outlet' => $outletId,
        ]);
    }

    public function saldo()
    {
        $outletId = $this->outletId();

        $topups = BalanceTransaction::with('user')
            ->where('outlet_id', $outletId)
            ->where('type', BalanceTransaction::TYPE_TOPUP)
            ->latest()
            ->limit(20)
            ->get(['id', 'user_id', 'amount', 'kasir_id', 'note', 'created_at']);

        return Inertia::render('Kasir/Saldo', [
            'outlets' => Outlet::orderBy('id')->get(['id', 'name']),
            'outlet' => $outletId,
            'topups' => $topups,
        ]);
    }

    public function userByNik(Request $request)
    {
        $user = User::where('nik', $request->input('nik'))->first();

        if (! $user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'nik' => $user->nik,
            'balance' => UserBalance::balanceOf($user->id, $this->outletId($request)),
        ]);
    }

    public function topUp(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'required|exists:users,nik',
            'amount' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        $outletId = $this->outletId($request);
        $user = User::where('nik', $validated['nik'])->firstOrFail();

        DB::transaction(function () use ($user, $outletId, $validated) {
            UserBalance::credit($user->id, $outletId, $validated['amount']);
            BalanceTransaction::create([
                'user_id' => $user->id,
                'outlet_id' => $outletId,
                'type' => BalanceTransaction::TYPE_TOPUP,
                'amount' => $validated['amount'],
                'kasir_id' => auth()->id(),
                'note' => $validated['note'] ?? null,
            ]);
        });

        $balance = UserBalance::balanceOf($user->id, $outletId);
        $outletName = Outlet::find($outletId)->name;

        return back()->with('flash', ['success' => "Saldo {$user->name} ({$outletName}) bertambah {$validated['amount']} Coin. Saldo kini {$balance}."]);
    }

    public function pay(Order $order, Request $request)
    {
        $user = auth()->user();
        abort_if($user->outlet_id && $order->outlet_id !== $user->outlet_id, 403, 'Bukan pesanan kantin Anda.');

        DB::transaction(function () use ($order) {
            $order->lockForUpdate();
            abort_if($order->status !== Order::STATUS_PENDING, 422);

            $balance = UserBalance::balanceOf($order->user_id, $order->outlet_id);
            abort_if($balance < $order->total_amount, 422, 'Saldo tidak cukup.');

            UserBalance::debit($order->user_id, $order->outlet_id, $order->total_amount);
            BalanceTransaction::create([
                'user_id' => $order->user_id,
                'outlet_id' => $order->outlet_id,
                'type' => BalanceTransaction::TYPE_DEDUCTION,
                'amount' => $order->total_amount,
                'kasir_id' => auth()->id(),
                'note' => "Pembayaran {$order->nota_code}",
            ]);
            $order->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
                'paid_by' => auth()->id(),
            ]);
        });

        $referer = $request->headers->get('referer');
        $q = $referer ? parse_url($referer, PHP_URL_QUERY) : null;
        parse_str((string) $q, $params);

        return redirect()->route('kasir.index', [
            'q' => $params['q'] ?? '',
            'outlet' => $params['outlet'] ?? $order->outlet_id,
            'status' => $params['status'] ?? '',
        ])
            ->with('flash', ['success' => "Pesanan {$order->nota_code} lunas."]);
    }
}
