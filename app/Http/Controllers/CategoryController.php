<?php

namespace App\Http\Controllers;

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
}
