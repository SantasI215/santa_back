<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category', 'description', 'price', 'in_stock'];

    public function boxes()
    {
        return $this->belongsToMany(Box::class, 'box_product')->withPivot('quantity');
    }
}
