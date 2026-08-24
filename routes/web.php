<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::get('/auth/sso', [AuthController::class, 'redirectSso'])->name('sso.redirect')->middleware('guest');
Route::get('/callback', [AuthController::class, 'callbackSso'])->name('sso.callback')->middleware('guest');
Route::get('/pending-role', [AuthController::class, 'pendingRole'])->name('pending-role')->middleware('guest');
Route::post('/pending-role', [AuthController::class, 'submitRole'])->name('pending-role.submit')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index')
        ->middleware('permission:user.manage');
    Route::post('/users/{user}/approve', [AdminUserController::class, 'approve'])->name('users.approve')
        ->middleware('permission:user.manage');
    Route::post('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role')
        ->middleware('permission:user.manage');
    Route::post('/users/{user}/deactivate', [AdminUserController::class, 'deactivate'])->name('users.deactivate')
        ->middleware('permission:user.manage');

    // Master data (categories)
    Route::get('/masters/{entity}/create', [MasterController::class, 'create'])->name('masters.create')
        ->middleware('permission:category.create');
    Route::get('/masters/{entity}/{id}/edit', [MasterController::class, 'edit'])->name('masters.edit')
        ->middleware('permission:category.update');
    Route::get('/masters/{entity}', [MasterController::class, 'index'])->name('masters.index')
        ->middleware('permission:category.read');
    Route::post('/masters/{entity}', [MasterController::class, 'store'])->name('masters.store')
        ->middleware('permission:category.create');
    Route::put('/masters/{entity}/{id}', [MasterController::class, 'update'])->name('masters.update')
        ->middleware('permission:category.update');
    Route::delete('/masters/{entity}/{id}', [MasterController::class, 'destroy'])->name('masters.destroy')
        ->middleware('permission:category.delete');

    // Items
    Route::get('/items/{item}/foto', [ItemController::class, 'foto'])->name('items.foto');
    Route::resource('items', ItemController::class)
        ->middleware('permission:item.read|item.create|item.update|item.delete');

    // Stock
    Route::get('/stock', [StockController::class, 'today'])->name('stock.today')
        ->middleware('permission:stock.manage');
    Route::post('/stock', [StockController::class, 'save'])->name('stock.save')
        ->middleware('permission:stock.manage');

    // Menu (katalog + checkout)
    Route::get('/menu', [OrderController::class, 'catalog'])->name('menu.catalog');
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout')
        ->middleware('permission:order.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store')
        ->middleware('permission:order.create');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index')
        ->middleware('permission:order.read');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show')
        ->middleware('permission:order.read');

    // Kasir
    Route::get('/kasir', [PaymentController::class, 'index'])->name('kasir.index')
        ->middleware('permission:payment.manage');
    Route::get('/kasir/saldo', [PaymentController::class, 'saldo'])->name('kasir.saldo')
        ->middleware('permission:payment.manage');
    Route::get('/kasir/user', [PaymentController::class, 'userByNik'])->name('kasir.user')
        ->middleware('permission:payment.manage');
    Route::post('/kasir/topup', [PaymentController::class, 'topUp'])->name('kasir.topup')
        ->middleware('permission:payment.manage');
    Route::post('/kasir/{order}/pay', [PaymentController::class, 'pay'])->name('kasir.pay')
        ->middleware('permission:payment.manage');

    // Laporan
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')
        ->middleware('permission:report.read');
});
