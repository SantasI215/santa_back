<?php

namespace App\Http\Controllers;

use App\Models\BoxItem;
use App\Models\Item;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoxItemController extends Controller
{
    // Получить все товары для бокса
    public function getBoxItems($boxId): JsonResponse
    {
        $box = OrderItem::with(['box.categories'])->findOrFail($boxId);
        $categoryIds = $box->box->categories->pluck('id');

        // Получаем товары из нужных категорий
        $items = Item::whereHas('categories', function($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->get();

        // Получаем уже добавленные товары
        $boxItems = BoxItem::where('box_id', $boxId)->get();

        return response()->json([
            'items' => $items,
            'box_items' => $boxItems,
            'box' => $box
        ]);
    }

    // Добавить/обновить статус товара в боксе
    public function updateBoxItem(Request $request, $boxId): JsonResponse
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'status' => 'required|in:added,not_added'
        ]);

        $boxItem = BoxItem::updateOrCreate(
            [
                'box_id' => $boxId,
                'item_id' => $request->item_id
            ],
            ['status' => $request->status]
        );

        return response()->json($boxItem);
    }

    // Проверить и завершить сборку бокса
    public function completeBoxAssembly($boxId): JsonResponse
    {
        $box = OrderItem::findOrFail($boxId);
        $boxItems = BoxItem::where('box_id', $boxId)->get();

        // Проверяем, что все товары добавлены
        if ($boxItems->where('status', 'not_added')->count() > 0) {
            return response()->json([
                'error' => 'Не все товары добавлены в бокс'
            ], 400);
        }

        // Проверяем сумму товаров
        $totalPrice = $boxItems->sum(function ($boxItem) {
            return $boxItem->item->price;
        });

        if ($totalPrice > $box->box->price) {
            return response()->json([
                'error' => 'Сумма товаров превышает стоимость бокса'
            ], 400);
        }

        // Обновляем статус бокса
        $box->update(['status' => 'assembled']);

        // Проверяем, все ли боксы в заказе собраны
        $order = $box->order;
        if ($order->orderItems->where('status', '!=', 'assembled')->count() === 0) {
            $order->update(['status' => 'assembled']);
        }

        return response()->json([
            'message' => 'Бокс успешно собран',
            'box' => $box->fresh()
        ]);
    }

    // Рандомно наполнить бокс товарами
    public function fillBoxRandomly($boxId): JsonResponse
    {
        $box = OrderItem::with(['box.categories'])->findOrFail($boxId);
        $categoryIds = $box->box->categories->pluck('id');
        $maxPrice = $box->box->price;

        // Получаем все доступные товары из нужных категорий
        $availableItems = Item::whereHas('categories', function($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->get();

        // Очищаем текущие товары бокса
        BoxItem::where('box_id', $boxId)->delete();

        $selectedItems = [];
        $currentTotal = 0;

        // Пытаемся заполнить бокс товарами
        while($currentTotal < $maxPrice && count($availableItems) > 0) {
            // Выбираем случайный товар
            $randomIndex = rand(0, count($availableItems) - 1);
            $item = $availableItems[$randomIndex];

            // Проверяем, не превысит ли добавление товара максимальную цену
            if($currentTotal + $item->price <= $maxPrice) {
                $selectedItems[] = $item;
                $currentTotal += $item->price;
            }

            // Удаляем товар из доступных
            $availableItems = $availableItems->except($item->id);
        }

        // Сохраняем выбранные товары
        foreach($selectedItems as $item) {
            BoxItem::create([
                'box_id' => $boxId,
                'item_id' => $item->id,
                'status' => 'added'
            ]);
        }

        return response()->json([
            'message' => 'Бокс успешно наполнен товарами',
            'total_price' => $currentTotal,
            'items' => $selectedItems
        ]);
    }
}
