<?php

use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\Product;

test('depo görevlisi cannot access the discount codes page', function () {
    actingAsDepoGorevlisi();

    $response = $this->get(route('discount-codes.index'));

    $response->assertForbidden();
});

test('süper admin can create a product-scoped discount code', function () {
    actingAsSuperAdmin();

    $product = Product::factory()->create();

    $response = $this->post(route('discount-codes.store'), [
        'code' => 'YAZ2026',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'product_id' => $product->id,
    ]);

    $response->assertRedirect(route('discount-codes.index'));
    $this->assertDatabaseHas('discount_codes', [
        'code' => 'YAZ2026',
        'product_id' => $product->id,
        'category_id' => null,
    ]);
});

test('süper admin can create a category-scoped discount code with a minimum order amount', function () {
    actingAsSuperAdmin();

    $category = Category::factory()->create();

    $response = $this->post(route('discount-codes.store'), [
        'code' => 'KATEGORI50',
        'discount_type' => 'fixed',
        'discount_value' => 50,
        'min_order_amount' => 500,
        'category_id' => $category->id,
    ]);

    $response->assertRedirect(route('discount-codes.index'));
    $this->assertDatabaseHas('discount_codes', [
        'code' => 'KATEGORI50',
        'category_id' => $category->id,
        'product_id' => null,
        'min_order_amount' => 500,
    ]);
});

test('a discount code cannot target both a product and a category at once', function () {
    actingAsSuperAdmin();

    $product = Product::factory()->create();
    $category = Category::factory()->create();

    $response = $this->post(route('discount-codes.store'), [
        'code' => 'GECERSIZ',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'product_id' => $product->id,
        'category_id' => $category->id,
    ]);

    $response->assertSessionHasErrors(['product_id', 'category_id']);
});

test('a discount code requires either a product or a category', function () {
    actingAsSuperAdmin();

    $response = $this->post(route('discount-codes.store'), [
        'code' => 'HEDEFSIZ',
        'discount_type' => 'percentage',
        'discount_value' => 20,
    ]);

    $response->assertSessionHasErrors(['product_id', 'category_id']);
});

test('a percentage discount code value over 100 is rejected', function () {
    actingAsSuperAdmin();

    $product = Product::factory()->create();

    $response = $this->post(route('discount-codes.store'), [
        'code' => 'ASIRI',
        'discount_type' => 'percentage',
        'discount_value' => 150,
        'product_id' => $product->id,
    ]);

    $response->assertSessionHasErrors('discount_value');
});

test('a duplicate discount code is rejected', function () {
    actingAsSuperAdmin();

    $product = Product::factory()->create();
    DiscountCode::factory()->create(['code' => 'MEVCUT', 'product_id' => $product->id]);

    $response = $this->post(route('discount-codes.store'), [
        'code' => 'MEVCUT',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'product_id' => $product->id,
    ]);

    $response->assertSessionHasErrors('code');
});

test('a discount code applies to products matching its product scope', function () {
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();

    $discountCode = DiscountCode::factory()->create([
        'product_id' => $product->id,
        'category_id' => null,
    ]);

    expect($discountCode->appliesToProducts([$product]))->toBeTrue();
    expect($discountCode->appliesToProducts([$otherProduct]))->toBeFalse();
});

test('a discount code applies to products matching its category scope', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);
    $otherProduct = Product::factory()->create();

    $discountCode = DiscountCode::factory()->create([
        'product_id' => null,
        'category_id' => $category->id,
    ]);

    expect($discountCode->appliesToProducts([$product]))->toBeTrue();
    expect($discountCode->appliesToProducts([$otherProduct]))->toBeFalse();
});

test('discountAmountFor calculates a percentage discount', function () {
    $discountCode = DiscountCode::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 20,
    ]);

    expect($discountCode->discountAmountFor(1000))->toBe(200.0);
});

test('discountAmountFor calculates a fixed discount capped at the order total', function () {
    $discountCode = DiscountCode::factory()->create([
        'discount_type' => 'fixed',
        'discount_value' => 5000,
    ]);

    expect($discountCode->discountAmountFor(1000))->toBe(1000.0);
});
