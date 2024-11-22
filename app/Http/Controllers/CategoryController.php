<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        // Получаем все категории
        $categories = Category::all();

        // Возвращаем их в формате JSON
        return response()->json($categories);
    }
}
