<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

test('depo görevlisi cannot access the sales report page', function () {
    actingAsDepoGorevlisi();

    $response = $this->get(route('reports.sales'));

    $response->assertForbidden();
});

test('süper admin can view the sales report page', function () {
    actingAsSuperAdmin();

    $response = $this->get(route('reports.sales'));

    $response->assertOk();
});

test('the sales report only counts confirmed, shipped and completed orders towards the total', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();

    Order::factory()->create(['customer_id' => $customer->id, 'total_amount' => 100, 'status' => 'pending']);
    Order::factory()->create(['customer_id' => $customer->id, 'total_amount' => 200, 'status' => 'confirmed']);
    Order::factory()->create(['customer_id' => $customer->id, 'total_amount' => 300, 'status' => 'shipped']);
    Order::factory()->create(['customer_id' => $customer->id, 'total_amount' => 400, 'status' => 'completed']);
    Order::factory()->create(['customer_id' => $customer->id, 'total_amount' => 500, 'status' => 'cancelled']);

    $response = $this->get(route('reports.sales'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('currentMonth.total_sales', 900)
        ->where('currentMonth.order_count', 3)
    );
});

test('the sales report separates orders from the current and previous month', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();

    Order::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 1000,
        'status' => 'confirmed',
        'created_at' => now(),
    ]);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 2000,
        'status' => 'confirmed',
        'created_at' => now()->subMonthNoOverflow(),
    ]);

    // İki ay öncesi rapora hiç girmemeli.
    Order::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 5000,
        'status' => 'confirmed',
        'created_at' => now()->subMonthsNoOverflow(2),
    ]);

    $response = $this->get(route('reports.sales'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('currentMonth.total_sales', 1000)
        ->where('previousMonth.total_sales', 2000)
    );
});

test('the sales report lists top-selling products by quantity', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $bestSeller = Product::factory()->create(['name' => 'Çok Satan Ürün']);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'completed',
        'created_at' => now(),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $bestSeller->id,
        'quantity' => 10,
        'unit_price' => 100,
    ]);

    $response = $this->get(route('reports.sales'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('currentMonth.top_products.0.name', 'Çok Satan Ürün')
        ->where('currentMonth.top_products.0.total_quantity', 10)
        ->where('currentMonth.top_products.0.total_revenue', 1000)
    );
});

test('süper admin can download the sales report as a pdf', function () {
    actingAsSuperAdmin();

    $response = $this->get(route('reports.sales.pdf'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('depo görevlisi cannot download the sales report pdf', function () {
    actingAsDepoGorevlisi();

    $response = $this->get(route('reports.sales.pdf'));

    $response->assertForbidden();
});
