<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    // Получить всех товаров
    public function getAllItems()
    {
        $items = Item
            ::with('categories')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return response()->json($items);
    }
    // Получить всех товаров
    public function store(Request $request)
    {
        // Валидация входящих данных
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        // Если валидация не прошла
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Создание товара
        $item = Item::create([
            'name' => $request->name,
            'price' => $request->price,
            'quantity' => $request->quantity,
        ]);

        // Привязка категорий к товару
        $item->categories()->attach($request->categories);

        // Загрузка категорий вместе с товаром
        $item->load('categories');  // Это загрузит связанные категории

        // Возврат успешного ответа с категориями
        return response()->json($item, 201);
    }

    public function destroy($id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Товар не найден'], 404);
        }

        $item->delete();
        return response()->json(['message' => 'Товар удален'], 200);
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $item = Item::findOrFail($id);
        $item->update([
            'name' => $request->name,
            'price' => $request->price,
            'quantity' => $request->quantity,
        ]);

        $item->categories()->sync($request->categories);

        $item->load('categories');
        return response()->json($item, 200);
    }
}
