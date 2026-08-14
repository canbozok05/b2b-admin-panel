<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DiscountCodeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::middleware('role:Süper Admin')->group(function () {
        Route::get('dashboard', function () {
            $criticalStockQuery = Product::query()
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->whereColumn('products.stock_quantity', '<', 'categories.critical_stock_threshold');

            return Inertia::render('dashboard', [
                'totalCustomers' => Customer::count(),
                'monthlySales' => Order::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->whereNotIn('status', ['pending', 'cancelled'])
                    ->sum('total_amount'),
                'pendingOrders' => Order::where('status', 'pending')->count(),
                'criticalStock' => (clone $criticalStockQuery)->count(),
                'lowStockProducts' => (clone $criticalStockQuery)->get([
                    'products.name',
                    'products.stock_quantity',
                    'categories.critical_stock_threshold',
                ]),
            ]);
        })->name('dashboard');

        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/create', [CategoryController::class,
            'create'])->name('categories.create');
        Route::post('categories', [CategoryController::class,
            'store'])->name('categories.store');

        Route::get('categories/{id}/edit', [CategoryController::class,
            'edit'])->name('categories.edit');

        Route::put('categories/{id}', [CategoryController::class, 'update'])->name('categories.update');

        Route::delete('categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
        Route::post('campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
        Route::get('campaigns/{id}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit');
        Route::put('campaigns/{id}', [CampaignController::class, 'update'])->name('campaigns.update');
        Route::delete('campaigns/{id}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');

        Route::get('discount-codes', [DiscountCodeController::class, 'index'])->name('discount-codes.index');
        Route::get('discount-codes/create', [DiscountCodeController::class, 'create'])->name('discount-codes.create');
        Route::post('discount-codes', [DiscountCodeController::class, 'store'])->name('discount-codes.store');
        Route::get('discount-codes/{id}/edit', [DiscountCodeController::class, 'edit'])->name('discount-codes.edit');
        Route::put('discount-codes/{id}', [DiscountCodeController::class, 'update'])->name('discount-codes.update');
        Route::delete('discount-codes/{id}', [DiscountCodeController::class, 'destroy'])->name('discount-codes.destroy');

        Route::delete('products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('musteriler', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('musteriler/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('musteriler', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('musteriler/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('musteriler/{id}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('musteriler/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        Route::post('musteriler/{customerId}/adresler', [CustomerAddressController::class, 'store'])->name('customer-addresses.store');
        Route::put('musteriler/{customerId}/adresler/{id}', [CustomerAddressController::class, 'update'])->name('customer-addresses.update');
        Route::delete('musteriler/{customerId}/adresler/{id}', [CustomerAddressController::class, 'destroy'])->name('customer-addresses.destroy');

        Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{id}/edit', [OrderController::class, 'edit'])->name('orders.edit');
        Route::put('orders/{id}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');

        Route::get('sistem-yoneticileri', [AdminController::class, 'index'])->name('admins.index');
        Route::get('sistem-yoneticileri/create', [AdminController::class, 'create'])->name('admins.create');
        Route::post('sistem-yoneticileri', [AdminController::class, 'store'])->name('admins.store');
        Route::get('sistem-yoneticileri/{id}/edit', [AdminController::class, 'edit'])->name('admins.edit');
        Route::put('sistem-yoneticileri/{id}', [AdminController::class, 'update'])->name('admins.update');
        Route::delete('sistem-yoneticileri/{id}', [AdminController::class, 'destroy'])->name('admins.destroy');

    });

    Route::get('products', [ProductController::class,
        'index'])->name('products.index');

    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');

    Route::get('products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('products/{id}', [ProductController::class, 'update'])->name('products.update');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');

});

require __DIR__.'/settings.php';
