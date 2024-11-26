<?php

namespace App\Http\Controllers;

use App\Models\Box;
use Illuminate\Http\Request;

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
}
