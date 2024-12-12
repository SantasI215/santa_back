<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'box_id', 'price', 'status', 'collector_name'];

    public function box()
    {
        return $this->belongsTo(Box::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
