<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{

    // Метод для добавления в корзину
    public function addToCart($boxId)
    {
        $user = Auth::user(); // Получаем текущего пользователя

        // Добавляем новую запись в корзину
        $cart = Cart::create([
            'user_id' => $user->id,
            'box_id' => $boxId,
        ]);

        return response()->json([
            'message' => 'Товар добавлен в корзину',
            'cartItemId' => $cart->id, // Возвращаем уникальный ID записи корзины
        ]);
    }


    // Метод для отображения корзины текущего пользователя
    public function viewCart()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Пользователь не авторизован',
            ], 401);
        }

        $cartItems = Cart::with('box')
            ->where('user_id', $user->id)
            ->get()
            ->filter(function ($item) {
                return $item->box !== null; // Убираем элементы без связанных боксов
            });

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Ваша корзина пуста',
            ], 204);
        }

        $totalPrice = $cartItems->sum(function ($item) {
            return $item->box->price; // Общая сумма без учета количества
        });

        return response()->json([
            'cart_items' => $cartItems,
            'total_price' => $totalPrice,
        ], 200);
    }



    // Метод для удаления товара из корзины
    public function removeFromCart($cartItemId)
    {
        $user = Auth::user(); // Получаем текущего пользователя

        // Находим запись в корзине по её уникальному ID
        $cartItem = Cart::where('id', $cartItemId)
            ->where('user_id', $user->id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'message' => 'Товар не найден в вашей корзине',
            ], 404);
        }

        // Удаляем товар из корзины
        $cartItem->delete();

        // Получаем обновленное состояние корзины
        $cartItems = Cart::with('box')
            ->where('user_id', $user->id)
            ->get();

        $totalPrice = $cartItems->sum(function ($item) {
            return $item->box->price;
        });

        return response()->json([
            'message' => 'Товар удалён из корзины',
            'cart_items' => $cartItems,
            'total_price' => $totalPrice,
        ], 200);
    }

}
