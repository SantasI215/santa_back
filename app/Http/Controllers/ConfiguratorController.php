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

        // Связываем категории с боксом
        $box->categories()->attach($validated['categories']);

        // Добавляем бокс в корзину
        Cart::create([
            'user_id' => $user->id,
            'box_id' => $box->id,
        ]);

        return response()->json([
            'message' => 'Бокс успешно создан и добавлен в корзину',
            'box' => $box,
        ]);
    }

}
