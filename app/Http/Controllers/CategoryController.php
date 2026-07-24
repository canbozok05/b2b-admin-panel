<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Category;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(){
        return Inertia::render('categories/index' , [
            'categories' => Category::with('parent')->get(),
        ]);
    }

    public function create(){
        return Inertia::render('categories/create', ['categories' => Category::all(),]);
    }

    public function store(Request $request){
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' =>'nullable|exists:categories,id',
            'is_active' =>'boolean',]);

            Category::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'parent_id' => $validated['parent_id'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
        
            ]);

        return redirect()->route('categories.index');
    }

}
