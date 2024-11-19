<?php

namespace App\Http\Controllers;

use App\Models\Box;
use Illuminate\Http\Request;

class BoxController extends Controller
{
    public function index()
    {
        $boxes = Box::with('items')->get();
        return response()->json($boxes);
    }
    public function indexNew()
    {
        $boxes = Box::latest()->take(4)->get();
        return response()->json($boxes);
    }

    public function show($id)
    {
        // Загружаем бокс вместе с товарами
        $box = Box::with('items', 'items.category')->find($id);

        if (!$box) {
            return response()->json(['message' => 'Бокс не найден'], 404);
        }

        return response()->json($box);
    }
}
