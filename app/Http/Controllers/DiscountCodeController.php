<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscountCodeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('discount-codes/index', [
            'discountCodes' => DiscountCode::with('product:id,name', 'category:id,name')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('discount-codes/create', [
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        DiscountCode::create($this->validated($request));

        return redirect()->route('discount-codes.index');
    }

    public function edit(int $id): Response
    {
        return Inertia::render('discount-codes/edit', [
            'discountCode' => DiscountCode::findOrFail($id),
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $discountCode = DiscountCode::findOrFail($id);
        $discountCode->update($this->validated($request, $discountCode->id));

        return redirect()->route('discount-codes.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        DiscountCode::findOrFail($id)->delete();

        return redirect()->route('discount-codes.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => 'required|string|max:50|unique:discount_codes,code,'.$ignoreId,
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => $request->input('discount_type') === 'percentage'
                ? 'required|numeric|min:0.01|max:100'
                : 'required|numeric|min:0.01|max:99999999.99',
            'min_order_amount' => 'nullable|numeric|min:0|max:99999999.99',
            'product_id' => 'nullable|exists:products,id|required_without:category_id|prohibits:category_id',
            'category_id' => 'nullable|exists:categories,id|required_without:product_id|prohibits:product_id',
        ]);
    }
}
