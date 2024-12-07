<?php
namespace App\Http\Controllers;

use App\Models\OrderItem;

class OrderItemController extends Controller
{
    public function index()
    {
        $orderItems = OrderItem::all();
        return response()->json($orderItems);
    }
}