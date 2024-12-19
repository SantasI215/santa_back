<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoxItem;
use App\Models\Category;
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

        // Максимальная доля одной категории
        $maxCategoryShare = 0.5; // 50% от стоимости бокса
        $maxCategoryBudget = $targetPrice * $maxCategoryShare;

        // Получаем все доступные товары из нужных категорий с учетом количества
        $availableItems = Item::whereHas('categories', function ($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->where('quantity', '>', 0)->get();

        // Группируем товары по категориям
        $itemsByCategory = $availableItems->groupBy(function ($item) {
            return $item->categories->pluck('id')->first();
        });

        $suggestedItems = [];
        $currentTotal = 0;
        $categoryTotals = []; // Для отслеживания стоимости товаров в каждой категории

        // Равномерно распределяем бюджет по категориям
        $budgetPerCategory = $targetPrice / count($categoryIds);

        foreach ($itemsByCategory as $categoryId => $items) {
            $categoryBudget = $budgetPerCategory;
            $categoryItems = $items->sortByDesc('price'); // Сначала берем дорогие товары

            foreach ($categoryItems as $item) {
                // Проверяем, что категория не превышает максимальную долю
                $currentCategoryTotal = $categoryTotals[$categoryId] ?? 0;
                if ($currentCategoryTotal >= $maxCategoryBudget) {
                    continue;
                }

                if ($item->price <= $categoryBudget && $item->quantity > 0) {
                    $quantity = min(
                        floor($categoryBudget / $item->price), // Сколько можем позволить в пределах бюджета
                        $item->quantity,                      // Доступное количество на складе
                        3                                     // Ограничиваем количеством на 1 бокс
                    );

                    if ($quantity > 0) {
                        $suggestedItems[] = [
                            'item_id' => $item->id,
                            'item' => $item,
                            'quantity' => $quantity,
                            'status' => 'Не добавлен'
                        ];

                        $currentTotal += $item->price * $quantity;
                        $categoryBudget -= $item->price * $quantity;
                        $categoryTotals[$categoryId] = ($categoryTotals[$categoryId] ?? 0) + ($item->price * $quantity);

                        if ($currentTotal >= $targetPrice * 0.95) {
                            break 2; // Если достигли 95% от стоимости, выходим из всех циклов
                        }
                    }
                }
            }
        }

        // Если после распределения по категориям осталось место, добираем товары из общего списка
        if ($currentTotal < $targetPrice * 0.95) {
            foreach ($availableItems->sortBy('price') as $item) {
                $categoryId = $item->categories->pluck('id')->first();

                // Проверяем, что категория не превышает максимальную долю
                $currentCategoryTotal = $categoryTotals[$categoryId] ?? 0;
                if ($currentCategoryTotal >= $maxCategoryBudget) {
                    continue;
                }

                if ($currentTotal + $item->price > $targetPrice || $item->quantity <= 0) {
                    continue;
                }

                $quantity = 1; // Добавляем по 1 штуке для оставшегося бюджета
                $suggestedItems[] = [
                    'item_id' => $item->id,
                    'item' => $item,
                    'quantity' => $quantity,
                    'status' => 'Не добавлен'
                ];

                $currentTotal += $item->price;
                $categoryTotals[$categoryId] = ($categoryTotals[$categoryId] ?? 0) + $item->price;

                if ($currentTotal >= $targetPrice * 0.95) {
                    break;
                }
            }
        }

        // Если товаров недостаточно для категории
        if (($categoryTotals[$categoryId] ?? 0) < $budgetPerCategory) {
            $missingCategories[] = $categoryId;
        }

        $missingCategoriesWithName = Category::whereIn('id', $missingCategories)->get(['id', 'name']);

        // Проверка на недокомплектацию
        $isIncomplete = $currentTotal < $targetPrice * 0.95;

        return response()->json([
            'suggestions' => $suggestedItems,
            'total_price' => $currentTotal,
            'is_incomplete' => $isIncomplete, // Флаг недокомплектации
            'missing_categories' => $missingCategoriesWithName, // Список категорий с нехваткой товаров
            'error' => $isIncomplete
                ? 'Недостаточно товаров для полного комплекта. Добавьте больше товаров в категории.'
                : null
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
            return DB::transaction(function () use ($request, $boxId) {
                $box = OrderItem::with(['box', 'order'])->findOrFail($boxId);
                $items = collect($request->items);

                // Проверяем, что все товары добавлены
                if ($items->where('status', 'Не добавлен')->count() > 0) {
                    return response()->json([
                        'error' => 'Не все товары добавлены в бокс'
                    ], 400);
                }

                // Проверяем наличие товаров, общую сумму и недокомплектацию
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

                // Проверяем недокомплектацию (считаем, что минимальный порог — 95% от стоимости бокса)
                $minRequiredPrice = $box->box->price * 0.95;
                if ($totalPrice < $minRequiredPrice) {
                    return response()->json([
                        'error' => "Бокс недокомплектован: сумма товаров ({$totalPrice}₽) меньше 95% от стоимости бокса ({$minRequiredPrice}₽)"
                    ], 400);
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
