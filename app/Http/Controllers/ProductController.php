<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::with('category', 'images')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->get()
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

        return Inertia::render('products/index', [
            'products' => $products,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('products/create', [
            'categories' => Category::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'description' => 'nullable|string|max:20000',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'stock_quantity' => 'required|integer|min:0|max:1000000',
            'is_published' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
        ]);

        $product = Product::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'],
            'is_published' => $validated['is_published'] ?? false,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');

                $product->images()->create([
                    'path' => $path,
                ]);
            }
        }

        return redirect()->route('products.index');
    }

    public function edit(int $id): Response
    {
        $product = Product::with('images')->findOrFail($id);

        return Inertia::render('products/edit', [
            'product' => $product,
            'categories' => Category::all(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,'.$id,
            'description' => 'nullable|string|max:20000',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'stock_quantity' => 'required|integer|min:0|max:1000000',
            'is_published' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
            'delete_image_ids' => 'nullable|array',
        ]);

        $product->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'],
            'is_published' => $validated['is_published'] ?? false,
        ]);

        if (! empty($validated['delete_image_ids'])) {
            $imagesToDelete = $product->images()->whereIn('id', $validated['delete_image_ids'])->get();

            foreach ($imagesToDelete as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');

                $product->images()->create([
                    'path' => $path,
                ]);
            }
        }

        return redirect()->route('products.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $product = Product::with('images')->findOrFail($id);

        if ($product->orderItems()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Bu ürüne bağlı sipariş kalemleri olduğu için silinemez.',
            ]);

            return redirect()->route('products.index');
        }

        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $product->delete();

        return redirect()->route('products.index');
    }
}
