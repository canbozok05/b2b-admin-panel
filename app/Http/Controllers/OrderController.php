<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('customer')
            ->orderByRaw("status = 'pending' desc")
            ->latest()
            ->get();

        return Inertia::render('orders/index', [
            'orders' => $orders,
        ]);
    }

    public function show($id)
    {
        $order = Order::with('customer', 'orderItems.product')->find($id);

        return Inertia::render('orders/show', [
            'order' => $order,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,shipped,completed,cancelled',
        ]);

        $order = Order::find($id);
        $order->update(['status' => $validated['status']]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Sipariş durumu güncellendi.',
        ]);

        return redirect()->route('orders.show', $id);
    }
}
