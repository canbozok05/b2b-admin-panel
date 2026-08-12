<?php

namespace App\Http\Controllers;

use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function store(Request $request, int $customerId): RedirectResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'address' => 'required|string|max:500',
        ]);

        CustomerAddress::create([
            'customer_id' => $customerId,
            'label' => $validated['label'],
            'address' => $validated['address'],
        ]);

        return redirect()->route('customers.edit', $customerId);
    }

    public function update(Request $request, int $customerId, int $id): RedirectResponse
    {
        $address = CustomerAddress::where('customer_id', $customerId)->findOrFail($id);

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'address' => 'required|string|max:500',
        ]);

        $address->update($validated);

        return redirect()->route('customers.edit', $customerId);
    }

    public function destroy(int $customerId, int $id): RedirectResponse
    {
        $address = CustomerAddress::where('customer_id', $customerId)->findOrFail($id);
        $address->delete();

        return redirect()->route('customers.edit', $customerId);
    }
}
