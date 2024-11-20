<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // Используем модель User для работы с пользователями

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json(['message' => 'Welcome to the admin dashboard']);
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
}
