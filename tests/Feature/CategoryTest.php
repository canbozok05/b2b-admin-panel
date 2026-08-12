<?php

use App\Models\Category;
use App\Models\Product;

test('depo görevlisi cannot access the categories page', function () {
    actingAsDepoGorevlisi();

    $response = $this->get(route('categories.index'));

    $response->assertForbidden();
});

test('süper admin can create a category', function () {
    actingAsSuperAdmin();

    $response = $this->post(route('categories.store'), [
        'name' => 'Elektronik',
        'is_active' => true,
        'vat_rate' => 20,
        'critical_stock_threshold' => 10,
    ]);

    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseHas('categories', [
        'name' => 'Elektronik',
        'slug' => 'elektronik',
        'vat_rate' => 20,
        'critical_stock_threshold' => 10,
    ]);
});

test('a category vat rate over 100 is rejected', function () {
    actingAsSuperAdmin();

    $response = $this->post(route('categories.store'), [
        'name' => 'Elektronik',
        'is_active' => true,
        'vat_rate' => 150,
        'critical_stock_threshold' => 10,
    ]);

    $response->assertSessionHasErrors('vat_rate');
    $this->assertDatabaseMissing('categories', ['name' => 'Elektronik']);
});

test('a negative category critical stock threshold is rejected', function () {
    actingAsSuperAdmin();

    $response = $this->post(route('categories.store'), [
        'name' => 'Elektronik',
        'is_active' => true,
        'vat_rate' => 20,
        'critical_stock_threshold' => -1,
    ]);

    $response->assertSessionHasErrors('critical_stock_threshold');
    $this->assertDatabaseMissing('categories', ['name' => 'Elektronik']);
});

test('a category with products cannot be deleted', function () {
    actingAsSuperAdmin();

    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    $response = $this->delete(route('categories.destroy', $category->id));

    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

test('an empty category can be deleted', function () {
    actingAsSuperAdmin();

    $category = Category::factory()->create();

    $response = $this->delete(route('categories.destroy', $category->id));

    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});
