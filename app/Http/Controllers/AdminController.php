<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item; 

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json(['message' => 'Welcome to the admin dashboard']);
    }

    public function getOrders()
    {
        $orders = Item::all();
        return response()->json($orders);
    }
    public function adminDashboard()
    {
        $user = auth()->user();

        if ($user && $user->role === 'admin') {
            
            return response()->json(['message' => 'Welcome to the admin panel']);
        }

        return response()->json(['error' => 'Access denied'], 403);
    }
}

