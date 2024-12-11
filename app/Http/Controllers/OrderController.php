<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    // Метод для отображения страницы оформления заказа
    public function checkout()
    {
        $user = Auth::user();
        $cartItems = Cart::where('user_id', $user->id)->with('box')->get();

        $totalPrice = $cartItems->sum(fn($item) => $item->box->price);

        return response()->json([
            'cart_items' => $cartItems,
            'total_price' => $totalPrice,
        ]);
    }

    // Метод для создания заказа
    public function placeOrder(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:255',
            'payment_method' => 'required|string|max:50',
        ]);

        $user = Auth::user();
        $cartItems = Cart::where('user_id', $user->id)->with('box')->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Корзина пуста'], 400);
        }

        // Создаем заказ
        $order = Order::create([
            'user_id' => $user->id,
            'address' => $request->address,
            'payment_method' => $request->payment_method,
            'total_price' => $cartItems->sum(fn($item) => $item->box->price),
            'status' => 'В обработке',
        ]);

        // Создаем элементы заказа
        foreach ($cartItems as $cartItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'box_id' => $cartItem->box_id,
                'price' => $cartItem->box->price,
            ]);
        }

        // Очищаем корзину
        Cart::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'Заказ успешно оформлен',
            'order' => $order->load('items.box'),
        ]);
    }

    // Метод для получения всех заказов текущего пользователя
    public function getUserOrders()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Не авторизован'], 401);
            }

            $orders = Order::where('user_id', $user->id)
                ->with(['items.box'])
                ->get();

            return response()->json(['orders' => $orders], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка сервера', 'error' => $e->getMessage()], 500);
        }
    }


    // Метод для получения истории заказов
    public function getOrderHistory(Request $request)
    {
        $userId = $request->user()->id;

        $orders = Order::where('user_id', $userId)
            ->with(['items.box'])
            ->get();

        return response()->json(['orders' => $orders]);
    }
}
