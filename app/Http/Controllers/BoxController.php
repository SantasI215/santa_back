<?php

namespace App\Http\Controllers;

use App\Models\Box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $box = Box::with('items', 'items.categories')->find($id);

        if (!$box) {
            return response()->json(['message' => 'Бокс не найден'], 404);
        }

        return response()->json($box);
    }

    public function store(Request $request)
    {
        // Валидация входных данных
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Создание нового бокса
        $box = Box::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'price' => $request->input('price'),
        ]);

        return response()->json([
            'message' => 'Бокс успешно добавлен',
            'box' => $box,
        ], 201);
    }
}
