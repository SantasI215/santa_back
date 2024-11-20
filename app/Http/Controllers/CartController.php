<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cartBoxes = Cart::with('box')
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json($cartBoxes);
    }

    // Метод для добавления в корзину
    public function addToCart(Request $request, $boxId)
    {
        $user = auth()->user();

        // Проверяем, существует ли уже товар в корзине
        $cartItem = Cart::where('user_id', $user->id)
            ->where('box_id', $boxId)
            ->first();

        if ($cartItem) {
            // Если товар уже есть, увеличиваем количество
            $cartItem->quantity++;
            $cartItem->save();
        } else {
            // Если товара нет в корзине, добавляем новый элемент
            Cart::create([
                'user_id' => $user->id,
                'box_id' => $boxId,
                'quantity' => 1,
            ]);
        }

        return response()->json(['message' => 'Товар добавлен в корзину']);
    }

    public function removeFromCart(Request $request)
    {
        $user = auth()->user(); // Получаем текущего пользователя
        $cartItem = Cart::where('user_id', $user->id)->where('box_id', $request->box_id)->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Товар не найден в корзине'], 404);
        }

        try {
            // Удаляем товар из корзины
            $cartItem->delete();
            return response()->json(['message' => 'Товар удален из корзины']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка при удалении товара из корзины', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Cart $cartItem)
    {
        $cartItem->delete();

        return response()->json(['message' => 'Товар удален из корзины']);
    }
}
