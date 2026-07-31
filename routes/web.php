<?php
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Http\Controllers\ProductController;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
    return Inertia::render('dashboard', [
        'totalCustomers' => Customer::count(),
        'monthlySales' => Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_amount'),
        'pendingOrders' => Order::where('status', 'pending')->count(),
        'criticalStock' => Product::where('stock_quantity' , '<' ,10)->count(),
        'lowStockProducts' => Product::where('stock_quantity','<',10)->get(['name','stock_quantity']),
    ]);
})->name('dashboard');

Route::get('categories', [CategoryController::class,'index'])->name('categories.index');
Route::get('categories/create', [CategoryController::class,
'create'])->name('categories.create');
Route::post('categories', [CategoryController::class,
'store'])->name('categories.store');

Route::get('categories/{id}/edit', [CategoryController::class,
'edit'])->name('categories.edit');

Route::put('categories/{id}' , [CategoryController::class, 'update'])->name('categories.update');

Route::delete('categories/{id}',[CategoryController::class, 'destroy'])->name('categories.destroy');

Route::get('products', [ProductController::class,
'index'])->name('products.index');

Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
Route::post('products', [ProductController::class, 'store'])->name('products.store');

Route::get('products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
Route::put('products/{id}', [ProductController::class, 'update'])->name('products.update');
Route::delete('products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');

});

require __DIR__.'/settings.php';
