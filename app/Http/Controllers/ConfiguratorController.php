<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Cart;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConfiguratorController extends Controller
{
    public function createAndAddToCart(Request $request)
    {
        $user = Auth::user();

        // Валидируем входящие данные
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        // Создаем новый бокс
        $box = Box::create([
            'name' => 'Индивидуальный подарок',
            'description' => 'Сгенерированный подарок с выбранными категориями',
            'price' => $validated['amount'],
            'is_official' => false,
        ]);

        // Добавляем бокс в корзину
        Cart::create([
            'user_id' => $user->id,
            'box_id' => $box->id,
            'quantity' => 1, // По умолчанию 1
        ]);

        return response()->json([
            'message' => 'Бокс успешно создан и добавлен в корзину',
            'box' => $box,
        ]);
    }

    public function generateBox(Request $request)
    {
        $amount = $request->input('amount'); // Максимальная сумма
        $categories = $request->input('categories'); // Массив выбранных категорий

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
                $boxItems[] = $item;
                $totalPrice += $itemPrice;

                // Обновляем счётчик количества данного товара
                $itemCounts[$item->id] = ($itemCounts[$item->id] ?? 0) + 1;
            }

            // Если сумма приближается к целевой, выходим из цикла
            if ($totalPrice >= $amount * 0.95) {
                break;
            }
        }

        // Формируем данные для возврата
        $box = [
            'name' => 'Подарочный бокс',
            'description' => 'Сгенерированный подарок',
            'price' => $totalPrice,
            'items' => $boxItems,
        ];

        return response()->json(['box' => $box]);
    }
}
