<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
    return Inertia::render('dashboard', [
        'totalCustomers' => 50,
        'monthlySales' => 24500.50,
        'pendingOrders' => 3,
        'criticalStock' => 4,
    ]);
})->name('dashboard');
});

require __DIR__.'/settings.php';
