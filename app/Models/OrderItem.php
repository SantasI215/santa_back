<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'box_id', 'quantity', 'price'];

    public function box()
    {
        return $this->belongsTo(Box::class);
    }
}
