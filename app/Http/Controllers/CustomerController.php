<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('customers/index', [
            'customers' => Customer::all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('customers/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
            'address_label' => 'required|string|max:100',
            'address_text' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            $customer = Customer::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
            ]);

            $customer->addresses()->create([
                'label' => $validated['address_label'],
                'address' => $validated['address_text'],
            ]);
        });

        return redirect()->route('customers.index');
    }

    public function edit(int $id): Response
    {
        return Inertia::render('customers/edit', [
            'customer' => Customer::with('addresses')->findOrFail($id),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email,'.$id,
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $customer = Customer::findOrFail($id);

        if ($customer->orders()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Bu müşteriye bağlı siparişler olduğu için silinemez.',
            ]);

            return redirect()->route('customers.index');
        }

        $customer->delete();

        return redirect()->route('customers.index');
    }
}
