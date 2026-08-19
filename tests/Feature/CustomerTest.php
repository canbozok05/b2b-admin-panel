<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;

test('süper admin can create a customer with a required first address', function () {
    actingAsSuperAdmin();

    $response = $this->post(route('customers.store'), [
        'name' => 'Ahmet Yılmaz',
        'email' => 'ahmet@example.com',
        'status' => 'active',
        'address_label' => 'Ev',
        'address_text' => 'Test mahallesi, test sokak no: 1',
    ]);

    $response->assertRedirect(route('customers.index'));

    $customer = Customer::where('email', 'ahmet@example.com')->first();
    expect($customer)->not->toBeNull();

    $this->assertDatabaseHas('customer_addresses', [
        'customer_id' => $customer->id,
        'label' => 'Ev',
        'address' => 'Test mahallesi, test sokak no: 1',
    ]);
});

test('a customer cannot be created without a first address', function () {
    actingAsSuperAdmin();

    $response = $this->post(route('customers.store'), [
        'name' => 'Ahmet Yılmaz',
        'email' => 'ahmet@example.com',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors(['address_label', 'address_text']);
    $this->assertDatabaseMissing('customers', ['email' => 'ahmet@example.com']);
});

test('a customer name over 255 characters is rejected', function () {
    actingAsSuperAdmin();

    $response = $this->post(route('customers.store'), [
        'name' => str_repeat('a', 256),
        'email' => 'ahmet@example.com',
        'status' => 'active',
        'address_label' => 'Ev',
        'address_text' => 'Test adresi',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseMissing('customers', ['email' => 'ahmet@example.com']);
});

test('a first address text over 500 characters is rejected', function () {
    actingAsSuperAdmin();

    $response = $this->post(route('customers.store'), [
        'name' => 'Ahmet Yılmaz',
        'email' => 'ahmet@example.com',
        'status' => 'active',
        'address_label' => 'Ev',
        'address_text' => str_repeat('a', 501),
    ]);

    $response->assertSessionHasErrors('address_text');
    $this->assertDatabaseMissing('customers', ['email' => 'ahmet@example.com']);
});

test('a customer phone over 20 characters is rejected', function () {
    actingAsSuperAdmin();

    $response = $this->post(route('customers.store'), [
        'name' => 'Ahmet Yılmaz',
        'email' => 'ahmet@example.com',
        'phone' => str_repeat('1', 21),
        'status' => 'active',
        'address_label' => 'Ev',
        'address_text' => 'Test adresi',
    ]);

    $response->assertSessionHasErrors('phone');
    $this->assertDatabaseMissing('customers', ['email' => 'ahmet@example.com']);
});

test('customers can be searched by name with Turkish-insensitive casing', function () {
    actingAsSuperAdmin();

    Customer::factory()->create(['name' => 'Şahnur Okur']);
    Customer::factory()->create(['name' => 'Cem Keseroğlu']);

    $response = $this->get(route('customers.index', ['search' => 'ŞAHNUR']));

    $response->assertInertia(fn ($page) => $page
        ->has('customers', 1)
        ->where('customers.0.name', 'Şahnur Okur')
    );
});

test('customers can be searched by email', function () {
    actingAsSuperAdmin();

    Customer::factory()->create(['email' => 'unique-search@example.com']);
    Customer::factory()->create(['email' => 'other@example.com']);

    $response = $this->get(route('customers.index', ['search' => 'unique-search']));

    $response->assertInertia(fn ($page) => $page->has('customers', 1));
});

test('customers can be searched by phone', function () {
    actingAsSuperAdmin();

    Customer::factory()->create(['phone' => '05551234567']);
    Customer::factory()->create(['phone' => '05559876543']);

    $response = $this->get(route('customers.index', ['search' => '1234567']));

    $response->assertInertia(fn ($page) => $page->has('customers', 1));
});

test('customers can be filtered by status', function () {
    actingAsSuperAdmin();

    Customer::factory()->create(['status' => 'active']);
    Customer::factory()->create(['status' => 'inactive']);

    $response = $this->get(route('customers.index', ['status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page
        ->has('customers', 1)
        ->where('customers.0.status', 'inactive')
    );
});

test('depo görevlisi cannot view a customer detail page', function () {
    actingAsDepoGorevlisi();

    $customer = Customer::factory()->create();

    $response = $this->get(route('customers.show', $customer->id));

    $response->assertForbidden();
});

test('süper admin can view a customer detail page with their order history', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Test adresi']);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'order_number' => 'ORD-999999',
        'status' => 'completed',
    ]);

    $response = $this->get(route('customers.show', $customer->id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('customer.id', $customer->id)
        ->has('orders', 1)
        ->where('orders.0.order_number', 'ORD-999999')
    );
});
