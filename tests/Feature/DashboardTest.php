<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('super admins can visit the dashboard', function () {
    actingAsSuperAdmin();

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('non-admin authenticated users cannot visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertForbidden();
});

test('monthly sales only counts confirmed, shipped and completed orders', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();

    Order::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 100,
        'status' => 'pending',
    ]);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 200,
        'status' => 'confirmed',
    ]);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 300,
        'status' => 'shipped',
    ]);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 400,
        'status' => 'completed',
    ]);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 500,
        'status' => 'cancelled',
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page->where('monthlySales', 900));
});

test('critical stock uses each product\'s own category threshold', function () {
    actingAsSuperAdmin();

    $phoneCategory = Category::factory()->create(['critical_stock_threshold' => 15]);
    $sofaCategory = Category::factory()->create(['critical_stock_threshold' => 5]);

    // Telefon: 10 adet stok, eşik 15 -> kritik.
    Product::factory()->create([
        'category_id' => $phoneCategory->id,
        'stock_quantity' => 10,
    ]);

    // Koltuk takımı: 10 adet stok, eşik 5 -> kritik değil.
    Product::factory()->create([
        'category_id' => $sofaCategory->id,
        'stock_quantity' => 10,
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('criticalStock', 1)
        ->has('lowStockProducts', 1)
    );
});
