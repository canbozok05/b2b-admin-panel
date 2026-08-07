<?php

use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

test('depo görevlisi can update an order status', function () {
    Mail::fake();

    actingAsDepoGorevlisi();

    $order = Order::factory()->create(['status' => 'pending']);

    $response = $this->patch(route('orders.updateStatus', $order->id), [
        'status' => 'shipped',
    ]);

    $response->assertRedirect(route('orders.show', $order->id));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => 'shipped',
    ]);
});

test('an invalid order status is rejected', function () {
    actingAsSuperAdmin();

    $order = Order::factory()->create(['status' => 'pending']);

    $response = $this->patch(route('orders.updateStatus', $order->id), [
        'status' => 'not-a-real-status',
    ]);

    $response->assertSessionHasErrors('status');
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => 'pending',
    ]);
});

test('updating an order status sends the customer an email', function () {
    Mail::fake();

    actingAsSuperAdmin();

    $order = Order::factory()->create(['status' => 'pending']);

    $this->patch(route('orders.updateStatus', $order->id), [
        'status' => 'confirmed',
    ]);

    Mail::assertSent(OrderStatusUpdated::class);
});
