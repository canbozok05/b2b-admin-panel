<?php

namespace App\Http\Controllers;
use App\Models\Product;
use Inertia\Inertia;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request){
        $products = Product::with('category')
            ->when ($request->search, function ($query, $search){
                $query->where('name','like', "%{$search}%");
            })
            ->get();

        return Inertia::render('products/index',[
            'products' => $products,
            'filters' => $request->only('search'),
        ]);
    }
}
