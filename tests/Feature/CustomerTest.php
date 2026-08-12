<?php

use App\Models\Customer;

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
