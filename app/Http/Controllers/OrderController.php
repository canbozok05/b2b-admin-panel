<?php

namespace App\Http\Controllers;

use App\Mail\OrderStatusUpdated;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
        $order = Order::with('customer', 'orderItems.product')->findOrFail($id);

        return Inertia::render('orders/show', [
            'order' => $order,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('orders/create', [
            'customers' => Customer::all(),
            'products' => Product::all(['id', 'name', 'sku', 'price', 'stock_quantity']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        foreach ($validated['items'] as $item) {
            $product = Product::findOrFail((int) $item['product_id']);

            if ($item['quantity'] > $product->stock_quantity) {
                return back()->withErrors([
                    'items' => "\"{$product->name}\" için yeterli stok yok (mevcut: {$product->stock_quantity}).",
                ]);
            }
        }

        DB::transaction(function () use ($validated) {
            do {
                $orderNumber = 'ORD-'.random_int(100000, 999999);
            } while (Order::where('order_number', $orderNumber)->exists());

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail((int) $item['product_id']);
                $totalAmount += $product->price * $item['quantity'];
            }

            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail((int) $item['product_id']);

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                ]);

                $product->decrement('stock_quantity', $item['quantity']);
            }
        });

        return redirect()->route('orders.index');
    }

    public function edit(int $id): Response
    {
        $order = Order::with('orderItems.product', 'customer')->findOrFail($id);

        return Inertia::render('orders/edit', [
            'order' => $order,
            'customers' => Customer::all(),
            'products' => Product::all(['id', 'name', 'sku', 'price', 'stock_quantity']),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $order = Order::with('orderItems')->findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($order, $validated) {
            foreach ($order->orderItems as $oldItem) {
                Product::where('id', $oldItem->product_id)->increment('stock_quantity', $oldItem->quantity);
            }
            $order->orderItems()->delete();

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail((int) $item['product_id']);
                $totalAmount += $product->price * $item['quantity'];
            }

            $order->update([
                'customer_id' => $validated['customer_id'],
                'total_amount' => $totalAmount,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail((int) $item['product_id']);

                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
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
