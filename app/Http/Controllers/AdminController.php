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

    public function getAllOrders()
    {
        $orders = Order
            ::with([
                'user',
                'orderItems.box' => function ($query) {
                    $query->select('id', 'name');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($orders);
    }

    public function getOrders()
    {
        $user = auth()->user();

        $orders = Order::with([
            'user',
            'orderItems.box' => function ($query) {
                $query->select('id', 'name');
            },
        ])
            ->where(function ($query) use ($user) {
                $query->whereNull('collector_name') // Боксы без сборщика
                ->orWhere('collector_name', $user->name); // Боксы текущего сборщика
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }


    public function assignCollector(Request $request, $boxId)
    {
        $user = auth()->user(); // Получаем текущего пользователя

        // Находим OrderItem по переданному ID
        $orderItem = OrderItem::findOrFail($boxId);

        // Проверяем, связан ли OrderItem с заказом (Order)
        $order = $orderItem->order; // Предполагается связь с моделью Order через отношение
        if (!$order) {
            return response()->json(['message' => 'Order not found for this item.'], 404);
        }

        // Записываем имя сборщика в заказ
        $order->collector_name = $user->name;
        $order->save();

        return response()->json(['message' => 'Collector assigned successfully.']);
    }








    // public function getOrders()
    // {
    //     $orders = Item::all();
    //     return response()->json($orders);
    // }

    public function adminDashboard()
    {
        $user = auth()->user();

        if ($user && $user->role === 'admin') {
            return response()->json(['message' => 'Welcome to the admin panel']);
        }

        return response()->json(['error' => 'Access denied'], 403);
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
