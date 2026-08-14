<?php

use App\Mail\OrderStatusUpdated;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Product;
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

test('creating an order deducts stock and snapshots the unit price', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $address = CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Test adresi']);
    $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 50]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'customer_address_id' => $address->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 3],
        ],
    ]);

    $response->assertRedirect(route('orders.index'));

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock_quantity' => 7,
    ]);

    $order = Order::latest()->first();
    expect($order->total_amount)->toEqual(150);

    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => 50,
    ]);
});

test('an order cannot be created for more than the available stock', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $address = CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Test adresi']);
    $product = Product::factory()->create(['stock_quantity' => 2]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'customer_address_id' => $address->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 5],
        ],
    ]);

    $response->assertSessionHasErrors('items');
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock_quantity' => 2,
    ]);
    $this->assertDatabaseCount('orders', 0);
});

test('editing an order reconciles stock correctly', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $address = CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Test adresi']);
    $productA = Product::factory()->create(['stock_quantity' => 10, 'price' => 20]);
    $productB = Product::factory()->create(['stock_quantity' => 10, 'price' => 30]);

    $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'customer_address_id' => $address->id,
        'items' => [
            ['product_id' => $productA->id, 'quantity' => 4],
        ],
    ]);

    $productA->refresh();
    expect($productA->stock_quantity)->toBe(6);

    $order = Order::latest()->first();

    $response = $this->put(route('orders.update', $order->id), [
        'customer_id' => $customer->id,
        'customer_address_id' => $address->id,
        'items' => [
            ['product_id' => $productB->id, 'quantity' => 2],
        ],
    ]);

    $response->assertRedirect(route('orders.index'));

    $productA->refresh();
    $productB->refresh();

    // productA's stock is restored since it's no longer on the order.
    expect($productA->stock_quantity)->toBe(10);
    // productB's stock is freshly deducted.
    expect($productB->stock_quantity)->toBe(8);

    $order->refresh();
    expect($order->total_amount)->toEqual(60);
});

test('deleting an order restores stock', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $address = CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Test adresi']);
    $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 20]);

    $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'customer_address_id' => $address->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 4],
        ],
    ]);

    $product->refresh();
    expect($product->stock_quantity)->toBe(6);

    $order = Order::latest()->first();

    $response = $this->delete(route('orders.destroy', $order->id));

    $response->assertRedirect(route('orders.index'));

    $product->refresh();
    expect($product->stock_quantity)->toBe(10);
    $this->assertDatabaseMissing('orders', ['id' => $order->id]);
});

test('depo görevlisi cannot create an order', function () {
    actingAsDepoGorevlisi();

    $response = $this->get(route('orders.create'));

    $response->assertForbidden();
});

test('an order can be created with a delivery address', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $address = CustomerAddress::create([
        'customer_id' => $customer->id,
        'label' => 'Ev',
        'address' => 'Test mahallesi, test sokak no: 1',
    ]);
    $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 50]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'customer_address_id' => $address->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertRedirect(route('orders.index'));

    $order = Order::latest()->first();
    expect($order->customer_address_id)->toBe($address->id);
});

test('an order cannot use another customer\'s delivery address', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Test adresi']);
    $otherCustomer = Customer::factory()->create();
    $otherAddress = CustomerAddress::create([
        'customer_id' => $otherCustomer->id,
        'label' => 'Ev',
        'address' => 'Başka bir müşterinin adresi',
    ]);
    $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 50]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'customer_address_id' => $otherAddress->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors('customer_address_id');
    $this->assertDatabaseCount('orders', 0);
});

test('an order requires a new address when the customer has none', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 50]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors(['new_address_label', 'new_address_text']);
    $this->assertDatabaseCount('orders', 0);
});

test('an order creates and links a new address when the customer has none', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 50]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'new_address_label' => 'Ev',
        'new_address_text' => 'Test mahallesi, test sokak no: 1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertRedirect(route('orders.index'));

    $this->assertDatabaseHas('customer_addresses', [
        'customer_id' => $customer->id,
        'label' => 'Ev',
        'address' => 'Test mahallesi, test sokak no: 1',
    ]);

    $address = CustomerAddress::where('customer_id', $customer->id)->first();
    $order = Order::latest()->first();
    expect($order->customer_address_id)->toBe($address->id);
});

test('an order requires the delivery address to be selected when the customer has one', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Test adresi']);
    $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 50]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors('customer_address_id');
    $this->assertDatabaseCount('orders', 0);
});

test('a customer with registered addresses can still enter a new one for the order', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Eski adres']);
    $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 50]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'address_mode' => 'new',
        'new_address_label' => 'İş',
        'new_address_text' => 'Yeni iş adresi',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertRedirect(route('orders.index'));

    $this->assertDatabaseHas('customer_addresses', [
        'customer_id' => $customer->id,
        'label' => 'İş',
        'address' => 'Yeni iş adresi',
    ]);

    $newAddress = CustomerAddress::where('label', 'İş')->first();
    $order = Order::latest()->first();
    expect($order->customer_address_id)->toBe($newAddress->id);
    expect($customer->addresses()->count())->toBe(2);
});

test('a valid discount code reduces the order total', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $address = CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Test adresi']);
    $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 1000]);

    $discountCode = DiscountCode::factory()->create([
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'product_id' => $product->id,
        'category_id' => null,
    ]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'customer_address_id' => $address->id,
        'discount_code' => $discountCode->code,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ]);

    $response->assertRedirect(route('orders.index'));

    $order = Order::latest()->first();
    expect((float) $order->total_amount)->toBe(1600.0);
    expect((float) $order->discount_amount)->toBe(400.0);
    expect($order->discount_code_id)->toBe($discountCode->id);
});

test('an invalid discount code is rejected', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $address = CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Test adresi']);
    $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 1000]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'customer_address_id' => $address->id,
        'discount_code' => 'YOKBOYLEBIRKOD',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors('discount_code');
    $this->assertDatabaseCount('orders', 0);
});

test('a discount code that does not match any item in the order is rejected', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $address = CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Test adresi']);
    $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 1000]);
    $otherProduct = Product::factory()->create();

    $discountCode = DiscountCode::factory()->create([
        'product_id' => $otherProduct->id,
        'category_id' => null,
    ]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'customer_address_id' => $address->id,
        'discount_code' => $discountCode->code,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors('discount_code');
    $this->assertDatabaseCount('orders', 0);
});

test('a discount code below the minimum order amount is rejected', function () {
    actingAsSuperAdmin();

    $customer = Customer::factory()->create();
    $address = CustomerAddress::create(['customer_id' => $customer->id, 'label' => 'Ev', 'address' => 'Test adresi']);
    $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 100]);

    $discountCode = DiscountCode::factory()->create([
        'product_id' => $product->id,
        'category_id' => null,
        'min_order_amount' => 500,
    ]);

    $response = $this->post(route('orders.store'), [
        'customer_id' => $customer->id,
        'customer_address_id' => $address->id,
        'discount_code' => $discountCode->code,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors('discount_code');
    $this->assertDatabaseCount('orders', 0);
});
