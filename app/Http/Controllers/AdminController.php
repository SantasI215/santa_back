<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Item;

class AdminController extends Controller
{
    // Получить всех пользователей
    public function getAllUsers()
    {
        $users = User::all();
        return response()->json($users);
    }
    // Получить всех пользователей

    public function getAllOrders(Request $request)
    {
        $query = Order::with([
            'user',
            'orderItems.box' => function ($query) {
                $query->select('id', 'name');
            },
        ])->orderBy('created_at', 'desc');

        // Фильтр по номеру заказа
        if ($request->has('order_id') && !empty($request->input('order_id'))) {
            $query->where('id', $request->input('order_id'));
        }

        $orders = $query->get();

        return response()->json($orders);
    }

    public function getOrders()
    {
        $user = auth()->user();

        $orderItems = OrderItem::with([
            'box' => function ($query) {
                $query->select('id', 'name');
            },
            'order' => function ($query) {
                $query->select('id', 'user_id'); // Ограничиваем данные заказа
            },
            'order.user' => function ($query) {
                $query->select('id', 'name', 'email'); // Ограничиваем данные пользователя
            },
        ])
            ->where(function ($query) use ($user) {
                $query->whereNull('collector_name') // Товары без сборщика
                ->orWhere('collector_name', $user->name); // Товары, собираемые текущим сборщиком
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orderItems);
    }

    public function assignCollector(Request $request, $itemId)
    {
        $user = auth()->user(); // Получаем текущего пользователя

        $orderItem = OrderItem::findOrFail($itemId);

        // Проверяем, есть ли уже назначенный сборщик
        if (!empty($orderItem->collector_name) && $orderItem->collector_name !== $user->name) {
            return response()->json([
                'error' => 'Этот товар уже собирается другим сборщиком: ' . $orderItem->collector_name,
            ], 403);
        }

        // Назначаем текущего пользователя сборщиком
        $orderItem->collector_name = $user->name;
        $orderItem->save();

        return response()->json(['message' => 'Сборщик успешно назначен.']);
    }


    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
    public function deleteItem($id)
    {
        try {
            $item = Item::findOrFail($id);
            $item->delete();

            return response()->json(['message' => 'Item deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Item not found or error occurred'], 400);
        }
    }

}
