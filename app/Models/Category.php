<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Связь многие-ко-многим с боксами.
     */
    public function boxes()
    {
        return $this->belongsToMany(Box::class, 'box_category', 'category_id', 'box_id');
    }

    /**
     * Связь многие-ко-многим с товарами.
     */
    public function items()
    {
        return $this->belongsToMany(Item::class, 'category_item', 'category_id', 'item_id');
    }
}
