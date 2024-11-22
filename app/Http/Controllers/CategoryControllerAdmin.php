<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;

class CategoryControllerAdmin extends Controller
{
    public function generateBoxAdmin(Request $request)
    {
        $amount = $request->input('amount');
        $categories = $request->input('categories');

        // Фильтруем товары по категориям, цене и наличию
        $items = Item::whereHas('categories', function ($query) use ($categories) {
            $query->whereIn('categories.id', $categories);
        })
            ->where('price', '<=', $amount)
            ->where('in_stock', true)
            ->inRandomOrder()
            ->get();

        $totalPrice = 0;
        $boxItems = []; // Список товаров в боксе
        $itemCounts = []; // Счётчик количества каждого товара

        while ($totalPrice < $amount) {
            // Выбираем случайный товар
            $item = $items->random();
            $itemPrice = $item->price;

            // Проверяем, не превышено ли максимальное количество данного товара
            if (isset($itemCounts[$item->id]) && $itemCounts[$item->id] >= 3) {
                continue;
            }

            // Если товар можно добавить без превышения лимита, добавляем его в бокс
            if ($totalPrice + $itemPrice <= $amount) {
                // Увеличиваем количество этого товара в боксе
                $boxItems[] = [
                    'item' => $item,
                    'quantity' => 1,
                ];
                $totalPrice += $itemPrice;

                // Обновляем счётчик количества данного товара
                $itemCounts[$item->id] = ($itemCounts[$item->id] ?? 0) + 1;
            }

            // Если сумма приближается к целевой, выходим из цикла
            if ($totalPrice >= $amount * 0.95) {
                break;
            }
        }

        // Если сумма товаров меньше заданной, пробуем добавить дополнительные товары
        if ($totalPrice < $amount) {
            $remainingAmount = $amount - $totalPrice;
            $additionalItems = Item::whereHas('categories', function ($query) use ($categories) {
                $query->whereIn('categories.id', $categories);
            })
                ->where('price', '<=', $remainingAmount)
                ->where('in_stock', true)
                ->inRandomOrder()
                ->get();

            foreach ($additionalItems as $item) {
                // Проверяем ограничение на количество одинаковых товаров
                if (isset($itemCounts[$item->id]) && $itemCounts[$item->id] >= 3) {
                    continue;
                }

                if ($totalPrice + $item->price <= $amount) {
                    $boxItems[] = [
                        'item' => $item,
                        'quantity' => 1,
                    ];
                    $totalPrice += $item->price;
                    $itemCounts[$item->id] = ($itemCounts[$item->id] ?? 0) + 1;
                }

                if ($totalPrice >= $amount) {
                    break;
                }
            }
        }

        // Проверка на успешную генерацию бокса
        if (count($boxItems) > 0) {
            $box = Box::create([
                'name' => 'Подарочный бокс',
                'description' => 'Сгенерированный подарок',
                'price' => $totalPrice,
            ]);

            foreach ($boxItems as $boxItem) {
                $box->items()->attach($boxItem['item']->id, [
                    'quantity' => $boxItem['quantity'],
                ]);
            }

            return response()->json(['box' => $box->load('items')]);
        }

        return response()->json(['message' => 'Не удалось сформировать бокс'], 400);
    }
}
