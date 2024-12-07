<?php

namespace App\Http\Controllers;

use App\Models\Order;
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

    public function getOrders()
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
