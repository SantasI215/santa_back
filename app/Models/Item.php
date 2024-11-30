<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['name', 'description', 'price'];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_item', 'item_id', 'category_id');
    }
}

