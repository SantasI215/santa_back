<?php

namespace App\Http\Controllers;

use App\Models\Box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BoxController extends Controller
{
    public function index()
    {
        $boxes = Box
            ::where('is_official', true)
            ->with('categories')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($boxes);
    }

    public function indexAll(Request $request)
    {
        $query = Box::query();

        // Фильтр по категориям
        if ($request->has('categories')) {
            $categories = $request->input('categories');
            $query->whereHas('categories', function ($q) use ($categories) {
                $q->whereIn('id', $categories);
            });
        }

        // Сортировка
        if ($request->has('sort_by')) {
            $sortBy = $request->input('sort_by');
            switch ($sortBy) {
                case 'price':
                    $query->orderBy('price', 'asc');
                    break;
                case 'name':
                    $query->orderBy('name', 'asc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        }

        $boxes = $query->where('active', 'Активный')  // Фильтруем по булевому значению
            ->where('is_official', true)
            ->with('categories')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($boxes);
    }
    public function newBoxes()
    {
        $boxes = Box
            ::where('active', 'Активный')
            ->latest()
            ->take(4)
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
            'categories' => 'required|array',
            'categories.*' => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Создание нового бокса
            $box = Box::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'price' => $request->input('price'),
            ]);

            // Привязка категорий к боксу
            if ($request->has('categories')) {
                $box->categories()->attach($request->input('categories'));
            }

            return response()->json([
                'message' => 'Бокс успешно добавлен',
                'box' => $box->load('categories'), // Загрузка связанных категорий
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Произошла ошибка при создании бокса',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $box = Box::findOrFail($id);
            $box->delete();

            return response()->json(['message' => 'Бокс успешно удален.'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Бокс не найден.'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Не удалось удалить бокс.'], 500);
        }
    }
    // Обновить существующий бокс
    public function update(Request $request, $id)
    {
        $box = Box::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'categories' => 'array',
            'categories.*' => 'exists:categories,id',
            'active' => 'required|in:Активный,Неактивный', // Проверка допустимых значений
        ]);

        $box->update($request->only('name', 'description', 'price', 'active'));
        $box->categories()->sync($request->categories);

        return response()->json($box->load('categories'));
    }

    public function toggleActive($id)
    {
        $box = Box::findOrFail($id);
        $box->active = !$box->active;
        $box->save();

        return response()->json(['box' => $box, 'message' => 'Статус обновлен успешно.']);
    }
}
