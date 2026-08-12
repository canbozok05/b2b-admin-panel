<?php

use App\Models\Customer;
use App\Models\CustomerAddress;

test('depo görevlisi cannot add a customer address', function () {
    actingAsDepoGorevlisi();

    $customer = Customer::factory()->create();

    $response = $this->post(route('customer-addresses.store', $customer->id), [
        'label' => 'Ev',
        'address' => 'Test mahallesi, test sokak no: 1',
    ]);

    $response->assertForbidden();
});

test('süper admin can add a customer address', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();

    $response = $this->post(route('customer-addresses.store', $customer->id), [
        'label' => 'Ev',
        'address' => 'Test mahallesi, test sokak no: 1',
    ]);

    $response->assertRedirect(route('customers.edit', $customer->id));
    $this->assertDatabaseHas('customer_addresses', [
        'customer_id' => $customer->id,
        'label' => 'Ev',
    ]);
});

test('süper admin can update a customer address', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $address = CustomerAddress::create([
        'customer_id' => $customer->id,
        'label' => 'Ev',
        'address' => 'Eski adres',
    ]);

    $response = $this->put(route('customer-addresses.update', [$customer->id, $address->id]), [
        'label' => 'İş',
        'address' => 'Yeni adres',
    ]);

    $response->assertRedirect(route('customers.edit', $customer->id));
    $this->assertDatabaseHas('customer_addresses', [
        'id' => $address->id,
        'label' => 'İş',
        'address' => 'Yeni adres',
    ]);
});

test('süper admin can delete a customer address', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $address = CustomerAddress::create([
        'customer_id' => $customer->id,
        'label' => 'Ev',
        'address' => 'Test adresi',
    ]);

    $response = $this->delete(route('customer-addresses.destroy', [$customer->id, $address->id]));

    $response->assertRedirect(route('customers.edit', $customer->id));
    $this->assertDatabaseMissing('customer_addresses', ['id' => $address->id]);
});

test('an address belonging to another customer cannot be edited or deleted', function () {
    actingAsSuperAdmin();

    $customerA = Customer::factory()->create();
    $customerB = Customer::factory()->create();

    $address = CustomerAddress::create([
        'customer_id' => $customerA->id,
        'label' => 'Ev',
        'address' => 'Test adresi',
    ]);

    $this->put(route('customer-addresses.update', [$customerB->id, $address->id]), [
        'label' => 'Hacklendi',
        'address' => 'Hacklendi',
    ])->assertNotFound();

    $this->delete(route('customer-addresses.destroy', [$customerB->id, $address->id]))
        ->assertNotFound();

    $this->assertDatabaseHas('customer_addresses', [
        'id' => $address->id,
        'label' => 'Ev',
    ]);
});

test('an address text over 500 characters is rejected', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();

    $response = $this->post(route('customer-addresses.store', $customer->id), [
        'label' => 'Ev',
        'address' => str_repeat('a', 501),
    ]);

    $response->assertSessionHasErrors('address');
    $this->assertDatabaseMissing('customer_addresses', ['customer_id' => $customer->id]);
});

test('deleting a customer also deletes their addresses', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $address = CustomerAddress::create([
        'customer_id' => $customer->id,
        'label' => 'Ev',
        'address' => 'Test adresi',
    ]);

    $this->delete(route('customers.destroy', $customer->id));

    $this->assertDatabaseMissing('customer_addresses', ['id' => $address->id]);
});
