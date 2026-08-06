<?php

namespace App\Http\Controllers;

use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
