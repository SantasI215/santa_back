<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::all();
        return response()->json($items); 
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $item = Item::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
        ]);
        return response()->json($item, 201);
    }

    public function destroy($id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['message' => 'Товар не найден'], 404);
        }

        $item->delete();
        return response()->json(['message' => 'Товар удален'], 200);
    }
}
