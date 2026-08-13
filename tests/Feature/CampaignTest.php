<?php

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;

test('depo görevlisi cannot access the campaigns page', function () {
    actingAsDepoGorevlisi();

    $response = $this->get(route('campaigns.index'));

    $response->assertForbidden();
});

test('süper admin can create a product-scoped campaign', function () {
    actingAsSuperAdmin();

    $product = Product::factory()->create();

    $response = $this->post(route('campaigns.store'), [
        'name' => 'Yaz İndirimi',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'starts_at' => now()->subDay()->toDateTimeString(),
        'ends_at' => now()->addWeek()->toDateTimeString(),
        'product_id' => $product->id,
    ]);

    $response->assertRedirect(route('campaigns.index'));
    $this->assertDatabaseHas('campaigns', [
        'name' => 'Yaz İndirimi',
        'product_id' => $product->id,
        'category_id' => null,
    ]);
});

test('süper admin can create a category-scoped campaign', function () {
    actingAsSuperAdmin();

    $category = Category::factory()->create();

    $response = $this->post(route('campaigns.store'), [
        'name' => 'Kategori Kampanyası',
        'discount_type' => 'fixed',
        'discount_value' => 100,
        'starts_at' => now()->subDay()->toDateTimeString(),
        'ends_at' => now()->addWeek()->toDateTimeString(),
        'category_id' => $category->id,
    ]);

    $response->assertRedirect(route('campaigns.index'));
    $this->assertDatabaseHas('campaigns', [
        'name' => 'Kategori Kampanyası',
        'category_id' => $category->id,
        'product_id' => null,
    ]);
});

test('a campaign cannot target both a product and a category at once', function () {
    actingAsSuperAdmin();

    $product = Product::factory()->create();
    $category = Category::factory()->create();

    $response = $this->post(route('campaigns.store'), [
        'name' => 'Geçersiz Kampanya',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'starts_at' => now()->subDay()->toDateTimeString(),
        'ends_at' => now()->addWeek()->toDateTimeString(),
        'product_id' => $product->id,
        'category_id' => $category->id,
    ]);

    $response->assertSessionHasErrors(['product_id', 'category_id']);
});

test('a campaign requires either a product or a category', function () {
    actingAsSuperAdmin();

    $response = $this->post(route('campaigns.store'), [
        'name' => 'Hedefsiz Kampanya',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'starts_at' => now()->subDay()->toDateTimeString(),
        'ends_at' => now()->addWeek()->toDateTimeString(),
    ]);

    $response->assertSessionHasErrors(['product_id', 'category_id']);
});

test('a campaign end date must be after the start date', function () {
    actingAsSuperAdmin();

    $product = Product::factory()->create();

    $response = $this->post(route('campaigns.store'), [
        'name' => 'Ters Tarihli Kampanya',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'starts_at' => now()->toDateTimeString(),
        'ends_at' => now()->subDay()->toDateTimeString(),
        'product_id' => $product->id,
    ]);

    $response->assertSessionHasErrors('ends_at');
});

test('a percentage discount over 100 is rejected', function () {
    actingAsSuperAdmin();

    $product = Product::factory()->create();

    $response = $this->post(route('campaigns.store'), [
        'name' => 'Aşırı İndirim',
        'discount_type' => 'percentage',
        'discount_value' => 150,
        'starts_at' => now()->subDay()->toDateTimeString(),
        'ends_at' => now()->addWeek()->toDateTimeString(),
        'product_id' => $product->id,
    ]);

    $response->assertSessionHasErrors('discount_value');
});

test('a product with an active percentage campaign returns the discounted price', function () {
    $product = Product::factory()->create(['price' => 1000]);

    Campaign::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'product_id' => $product->id,
        'category_id' => null,
    ]);

    expect($product->discountedPrice())->toBe('800.00');
});

test('a product with an active fixed campaign returns the discounted price', function () {
    $product = Product::factory()->create(['price' => 1000]);

    Campaign::factory()->create([
        'discount_type' => 'fixed',
        'discount_value' => 150,
        'product_id' => $product->id,
        'category_id' => null,
    ]);

    expect($product->discountedPrice())->toBe('850.00');
});

test('a fixed discount never drops the price below zero', function () {
    $product = Product::factory()->create(['price' => 100]);

    Campaign::factory()->create([
        'discount_type' => 'fixed',
        'discount_value' => 500,
        'product_id' => $product->id,
        'category_id' => null,
    ]);

    expect($product->discountedPrice())->toBe('0.00');
});

test('a category campaign applies to all products in that category', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1000]);

    Campaign::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'product_id' => null,
        'category_id' => $category->id,
    ]);

    expect($product->discountedPrice())->toBe('900.00');
});

test('a product-specific campaign takes priority over a category campaign', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id, 'price' => 1000]);

    Campaign::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'product_id' => null,
        'category_id' => $category->id,
    ]);

    Campaign::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 50,
        'product_id' => $product->id,
        'category_id' => null,
    ]);

    expect($product->discountedPrice())->toBe('500.00');
});

test('an expired campaign no longer discounts the price', function () {
    $product = Product::factory()->create(['price' => 1000]);

    Campaign::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 50,
        'product_id' => $product->id,
        'category_id' => null,
        'starts_at' => now()->subWeeks(2),
        'ends_at' => now()->subWeek(),
    ]);

    expect($product->discountedPrice())->toBe((string) $product->price);
});

test('a future campaign does not discount the price yet', function () {
    $product = Product::factory()->create(['price' => 1000]);

    Campaign::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 50,
        'product_id' => $product->id,
        'category_id' => null,
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeeks(2),
    ]);

    expect($product->discountedPrice())->toBe((string) $product->price);
});

test('an order placed during an active campaign uses the discounted unit price', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Test adresi']);
    $product = Product::factory()->create(['price' => 1000, 'stock_quantity' => 10]);

    Campaign::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'product_id' => $product->id,
        'category_id' => null,
    ]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ]);

    $response->assertRedirect(route('orders.index'));
    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'unit_price' => 800,
    ]);
    $this->assertDatabaseHas('orders', [
        'total_amount' => 1600,
    ]);
});
