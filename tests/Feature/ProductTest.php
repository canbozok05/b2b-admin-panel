<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

test('depo görevlisi can view the products page', function () {
    actingAsDepoGorevlisi();

    $response = $this->get(route('products.index'));

    $response->assertOk();
});

test('depo görevlisi cannot delete a product', function () {
    actingAsDepoGorevlisi();

    $product = Product::factory()->create();

    $response = $this->delete(route('products.destroy', $product->id));

    $response->assertForbidden();
    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

test('süper admin can delete a product with no order history', function () {
    actingAsSuperAdmin();

    $product = Product::factory()->create();

    $response = $this->delete(route('products.destroy', $product->id));

    $response->assertRedirect(route('products.index'));
    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('a product referenced by an order item cannot be deleted', function () {
    actingAsSuperAdmin();

    $product = Product::factory()->create();
    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $response = $this->delete(route('products.destroy', $product->id));

    $response->assertRedirect(route('products.index'));
    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

test('creating a product requires a valid category', function () {
    actingAsSuperAdmin();

    $response = $this->post(route('products.store'), [
        'category_id' => 999,
        'name' => 'Test Ürünü',
        'sku' => 'TST-001',
        'price' => 100,
        'stock_quantity' => 5,
    ]);

    $response->assertSessionHasErrors('category_id');
});

test('süper admin can create a product', function () {
    actingAsSuperAdmin();

    $category = Category::factory()->create();

    $response = $this->post(route('products.store'), [
        'category_id' => $category->id,
        'name' => 'Test Ürünü',
        'sku' => 'TST-001',
        'price' => 149.90,
        'stock_quantity' => 10,
    ]);

    $response->assertRedirect(route('products.index'));
    $this->assertDatabaseHas('products', ['sku' => 'TST-001']);
});
