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
    ]);

    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseHas('categories', [
        'name' => 'Elektronik',
        'slug' => 'elektronik',
    ]);
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
