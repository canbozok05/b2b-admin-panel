<?php

namespace App\Http\Controllers;

use App\Mail\OrderStatusUpdated;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        $orders = Order::with('customer')
            ->orderByRaw("status = 'pending' desc")
            ->latest()
            ->get();

        return Inertia::render('orders/index', [
            'orders' => $orders,
        ]);
    }

    public function show(int $id): Response
    {
        $order = Order::with('customer', 'customerAddress', 'orderItems.product.category')->findOrFail($id);

        return Inertia::render('orders/show', [
            'order' => $order,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('orders/create', [
            'customers' => Customer::with('addresses')->get(),
            'products' => $this->productsWithCampaigns(),
        ]);
    }

    /**
     * @return Collection<int, Product>
     */
    private function productsWithCampaigns()
    {
        return Product::with('category:id,vat_rate')
            ->get(['id', 'category_id', 'name', 'sku', 'price', 'stock_quantity'])
            ->each(function (Product $product) {
                $campaign = $product->activeCampaign();
                $product->discounted_price = $product->discountedPrice();
                $product->active_campaign = $campaign ? [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'discount_type' => $campaign->discount_type,
                    'discount_value' => $campaign->discount_value,
                    'ends_at' => $campaign->ends_at,
                ] : null;
            });
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000000',
        ]);

        $customer = Customer::findOrFail((int) $validated['customer_id']);
        [$existingAddressId, $newAddress] = $this->resolveOrderAddress($request, $customer);

        foreach ($validated['items'] as $item) {
            $product = Product::findOrFail((int) $item['product_id']);

            if ($item['quantity'] > $product->stock_quantity) {
                return back()->withErrors([
                    'items' => "\"{$product->name}\" için yeterli stok yok (mevcut: {$product->stock_quantity}).",
                ]);
            }
        }

        DB::transaction(function () use ($validated, $customer, $existingAddressId, $newAddress) {
            do {
                $orderNumber = 'ORD-'.random_int(100000, 999999);
            } while (Order::where('order_number', $orderNumber)->exists());

            $customerAddressId = $newAddress
                ? $customer->addresses()->create($newAddress)->id
                : $existingAddressId;

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail((int) $item['product_id']);
                $totalAmount += $product->discountedPrice() * $item['quantity'];
            }

            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'customer_address_id' => $customerAddressId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail((int) $item['product_id']);

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->discountedPrice(),
                ]);

                $product->decrement('stock_quantity', $item['quantity']);
            }
        });

        return redirect()->route('orders.index');
    }

    /**
     * Siparişin teslimat adresini doğrular: müşterinin kayıtlı adresi varsa
     * bunlardan birinin seçilmesi, yoksa yeni bir adres girilmesi zorunludur.
     *
     * @return array{0: int|null, 1: array{label: string, address: string}|null}
     */
    private function resolveOrderAddress(Request $request, Customer $customer): array
    {
        if ($customer->addresses()->exists()) {
            $validated = $request->validate([
                'customer_address_id' => [
                    'required',
                    Rule::exists('customer_addresses', 'id')->where(
                        fn ($query) => $query->where('customer_id', $customer->id)
                    ),
                ],
            ]);

            return [(int) $validated['customer_address_id'], null];
        }

        $validated = $request->validate([
            'new_address_label' => 'required|string|max:100',
            'new_address_text' => 'required|string|max:500',
        ]);

        return [null, [
            'label' => $validated['new_address_label'],
            'address' => $validated['new_address_text'],
        ]];
    }

    public function edit(int $id): Response
    {
        $order = Order::with('orderItems.product', 'customer')->findOrFail($id);

        return Inertia::render('orders/edit', [
            'order' => $order,
            'customers' => Customer::with('addresses')->get(),
            'products' => $this->productsWithCampaigns(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $order = Order::with('orderItems')->findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000000',
        ]);

        $customer = Customer::findOrFail((int) $validated['customer_id']);
        [$existingAddressId, $newAddress] = $this->resolveOrderAddress($request, $customer);

        DB::transaction(function () use ($order, $validated, $customer, $existingAddressId, $newAddress) {
            foreach ($order->orderItems as $oldItem) {
                Product::where('id', $oldItem->product_id)->increment('stock_quantity', $oldItem->quantity);
            }
            $order->orderItems()->delete();

            $customerAddressId = $newAddress
                ? $customer->addresses()->create($newAddress)->id
                : $existingAddressId;

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail((int) $item['product_id']);
                $totalAmount += $product->discountedPrice() * $item['quantity'];
            }

            $order->update([
                'customer_id' => $validated['customer_id'],
                'customer_address_id' => $customerAddressId,
                'total_amount' => $totalAmount,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail((int) $item['product_id']);

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->discountedPrice(),
                ]);

                $product->decrement('stock_quantity', $item['quantity']);
            }
        });

        return redirect()->route('orders.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $order = Order::with('orderItems')->findOrFail($id);

        DB::transaction(function () use ($order) {
            foreach ($order->orderItems as $item) {
                Product::where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
            }

            $order->orderItems()->delete();
            $order->delete();
        });

        return redirect()->route('orders.index');
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,shipped,completed,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $validated['status']]);

        Mail::to($order->customer->email)->send(new OrderStatusUpdated($order));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Sipariş durumu güncellendi.',
        ]);

        return redirect()->route('orders.show', $id);
    }
}
