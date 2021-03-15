<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Category;

class CategoryController extends Controller
{
    public function addCategory(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|unique:categories',
        ]);

        $category = new Category;
        $category->name = $validatedData['name'];
        $category->save();
        return response()->json($category,201);
    }

    public function getCategory(){
        $categories = Category::all();
        return response()->json($categories,200);
    }
}
