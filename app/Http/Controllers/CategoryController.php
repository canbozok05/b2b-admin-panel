<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('categories/index', [
            'categories' => Category::with('parent')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('categories/create', ['categories' => Category::all()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean', ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,

        ]);

        return redirect()->route('categories.index');
    }

    public function edit(int $id): Response
    {
        return Inertia::render('categories/edit', [
            'category' => Category::findOrFail($id),
            'categories' => Category::all(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('categories.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        if ($category->products()->exists() || $category->children()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Bu kategoriye bağlı ürün veya alt kategori olduğu için silinemez.',
            ]);

            return redirect()->route('categories.index');
        }

        $category->delete();

        return redirect()->route('categories.index');
    }
}
