<?php

namespace App\Http\Controllers;
use App\Models\Product;
use Inertia\Inertia;
use App\Models\Category;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request){
        $products = Product::with('category','images')
            ->when ($request->search, function ($query, $search){
                $query->where('name','like', "%{$search}%");
            })
            ->get();

        return Inertia::render('products/index',[
            'products' => $products,
            'filters' => $request->only('search'),
        ]);
    }
    public function create(){
        return Inertia::render('products/create', [
            'categories' => Category::all(),
        ]);
    }

    public function store(Request $request){
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
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
}
