<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoxItem;
use App\Models\Item;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoxItemController extends Controller
{
    // Получить все товары для бокса
    public function getBoxItems($boxId): JsonResponse
    {
        $box = OrderItem::with(['box.categories'])->findOrFail($boxId);
        
        // Если бокс уже собран, возвращаем только добавленные товары
        if ($box->status === 'Собран') {
            $boxItems = BoxItem::with('item')->where('box_id', $boxId)->get();
            return response()->json([
                'box' => $box,
                'box_items' => $boxItems->map(function($boxItem) {
                    return [
                        'item_id' => $boxItem->item_id,
                        'item' => $boxItem->item,
                        'quantity' => $boxItem->quantity,
                        'status' => $boxItem->status
                    ];
                }),
                'is_assembled' => true
            ]);
        }

        $categoryIds = $box->box->categories->pluck('id');

        // Получаем товары из нужных категорий с учетом доступного количества
        $items = Item::whereHas('categories', function($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->where('quantity', '>', 0)->get();

        // Получаем уже добавленные товары
        $boxItems = BoxItem::where('box_id', $boxId)->get();

        return response()->json([
            'items' => $items,
            'box_items' => $boxItems,
            'box' => $box,
            'is_assembled' => false
        ]);
    }

    // Получить предложение товаров для бокса
    public function getSuggestions($boxId): JsonResponse
    {
        $box = OrderItem::with(['box.categories'])->findOrFail($boxId);
        
        // Проверяем, не собран ли уже бокс
        if ($box->status === 'Собран') {
            return response()->json([
                'error' => 'Этот бокс уже собран и не может быть изменен'
            ], 400);
        }

        $categoryIds = $box->box->categories->pluck('id');
        $targetPrice = $box->box->price;

        // Получаем все доступные товары из нужных категорий с учетом количества
        $availableItems = Item::whereHas('categories', function($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->where('quantity', '>', 0)->get();

        $suggestedItems = [];
        $currentTotal = 0;
        $maxAttempts = 100;
        $attempts = 0;

        // Сортируем товары по цене (от меньшей к большей)
        $sortedItems = $availableItems->sortBy('price');
        $usedItemIds = [];

        // Первый проход: пытаемся добавить по одному товару каждого типа
        foreach ($sortedItems as $item) {
            if ($currentTotal + $item->price > $targetPrice) {
                continue;
            }

            $suggestedItems[] = [
                'item_id' => $item->id,
                'item' => $item,
                'quantity' => 1,
                'status' => 'Не добавлен'
            ];

            $currentTotal += $item->price;
            $usedItemIds[] = $item->id;

            if ($currentTotal >= $targetPrice) {
                break;
            }
        }

        // Второй проход: увеличиваем количество товаров, начиная с самых дешевых
        if ($currentTotal < $targetPrice) {
            foreach ($suggestedItems as &$suggestion) {
                $item = $suggestion['item'];
                $maxAdditional = min(
                    floor(($targetPrice - $currentTotal) / $item->price),
                    $item->quantity - $suggestion['quantity'],
                    3 - $suggestion['quantity']
                );

                if ($maxAdditional > 0) {
                    $additional = $maxAdditional; // Берем максимально возможное количество
                    $suggestion['quantity'] += $additional;
                    $currentTotal += ($item->price * $additional);
                }

                if ($currentTotal >= $targetPrice * 0.95) { // Достаточно заполнить 95% стоимости
                    break;
                }
            }
        }

        // Если еще осталось место и есть неиспользованные товары, добавляем их
        if ($currentTotal < $targetPrice * 0.95) {
            foreach ($sortedItems as $item) {
                if (in_array($item->id, $usedItemIds)) {
                    continue;
                }

                if ($currentTotal + $item->price > $targetPrice) {
                    continue;
                }

                $suggestedItems[] = [
                    'item_id' => $item->id,
                    'item' => $item,
                    'quantity' => 1,
                    'status' => 'Не добавлен'
                ];

                $currentTotal += $item->price;
                $usedItemIds[] = $item->id;

                if ($currentTotal >= $targetPrice * 0.95) {
                    break;
                }
            }
        }

        return response()->json([
            'suggestions' => $suggestedItems,
            'total_price' => $currentTotal
        ]);
    }

    // Сохранить собранный бокс
    public function saveBox(Request $request, $boxId): JsonResponse
    {
        $box = OrderItem::findOrFail($boxId);
        
        // Проверяем, не собран ли уже бокс
        if ($box->status === 'Собран') {
            return response()->json([
                'error' => 'Этот бокс уже собран и не может быть изменен'
            ], 400);
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.status' => 'required|in:Добавлен,Не добавлен'
        ]);

        try {
            return DB::transaction(function() use ($request, $boxId) {
                $box = OrderItem::with(['box', 'order'])->findOrFail($boxId);
                $items = collect($request->items);

                // Проверяем, что все товары добавлены
                if ($items->where('status', 'Не добавлен')->count() > 0) {
                    return response()->json([
                        'error' => 'Не все товары добавлены в бокс'
                    ], 400);
                }

                // Проверяем наличие товаров и общую сумму
                $totalPrice = 0;
                foreach ($items as $boxItem) {
                    $item = Item::findOrFail($boxItem['item_id']);
                    
                    // Проверяем доступное количество
                    if ($item->quantity < $boxItem['quantity']) {
                        return response()->json([
                            'error' => "Недостаточно товара {$item->name} на складе (доступно: {$item->quantity}, требуется: {$boxItem['quantity']})"
                        ], 400);
                    }
                    
                    $totalPrice += $item->price * $boxItem['quantity'];
                }

                if ($totalPrice > $box->box->price) {
                    return response()->json([
                        'error' => "Сумма товаров ({$totalPrice}₽) превышает стоимость бокса ({$box->box->price}₽)"
                    ], 400);
                }

                // Удаляем старые товары
                BoxItem::where('box_id', $boxId)->delete();

                // Сохраняем новые товары и обновляем количество на складе
                foreach ($items as $boxItem) {
                    BoxItem::create([
                        'box_id' => $boxId,
                        'item_id' => $boxItem['item_id'],
                        'quantity' => $boxItem['quantity'],
                        'status' => $boxItem['status']
                    ]);

                    // Уменьшаем количество товара на складе
                    $item = Item::find($boxItem['item_id']);
                    $item->decrement('quantity', $boxItem['quantity']);
                }

                // Обновляем статус бокса
                $box->update(['status' => 'Собран']);

                // Проверяем, все ли боксы в заказе собраны
                $order = $box->order;
                if ($order && $order->orderItems->where('status', '!=', 'Собран')->count() === 0) {
                    $order->update(['status' => 'Отправлен']);
                }

                return response()->json([
                    'message' => 'Бокс успешно собран',
                    'box' => $box->fresh()
                ]);
            });
        } catch (\Exception $e) {
            \Log::error('Error in saveBox: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => 'Ошибка при сохранении бокса: ' . $e->getMessage()
            ], 500);
        }
    }
}
