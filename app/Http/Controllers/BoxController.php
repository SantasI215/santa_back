<?php

namespace App\Http\Controllers;

use App\Models\Box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BoxController extends Controller
{
    public function index()
    {
        $boxes = Box::where('is_official', true)
            ->get();
        return response()->json($boxes);
    }

    public function newBoxes()
    {
        $boxes = Box::where('is_official', true) // Фильтруем только официальные боксы
            ->latest() // Сортируем по дате создания в порядке убывания
            ->take(4) // Ограничиваем выборку до 4 записей
            ->get();

        return response()->json($boxes);
    }

    public function showDetail($id)
    {
        // Загружаем бокс вместе с категориями
        $box = Box::with('categories')->find($id);

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
