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
        $user = Auth::user();  // Получаем текущего пользователя

        // Проверяем, есть ли уже этот товар в корзине
        $cart = Cart::where('user_id', $user->id)->where('box_id', $boxId)->first();

        if (!$cart) {
            // Если товара нет в корзине, создаем новую запись
            $cart = Cart::create([
                'user_id' => $user->id,
                'box_id' => $boxId,
                'quantity' => 1,  // Начальное количество
            ]);
        } else {
            // Если товар уже в корзине, увеличиваем количество
            $cart->increment('quantity');
        }

        return response()->json([
            'message' => 'Товар добавлен в корзину',
            'cart' => $cart,
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
            return $item->box->price * $item->quantity;
        });

        $totalQuantity = $cartItems->sum('quantity');

        return response()->json([
            'cart_items' => $cartItems,
            'total_price' => $totalPrice,
            'total_quantity' => $totalQuantity,
        ], 200);
    }


    // Метод для удаления товара из корзины
    public function removeFromCart($boxId)
    {
        $user = Auth::user(); // Получаем текущего пользователя

        // Находим товар в корзине
        $cartItem = Cart::find($boxId)
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
            return $item->box->price * $item->quantity;
        });

        $totalQuantity = $cartItems->sum('quantity');

        return response()->json([
            'message' => 'Товар удален из корзины',
            'cart_items' => $cartItems,
            'total_price' => $totalPrice,
            'total_quantity' => $totalQuantity,
        ]);
    }

}
