<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // Указываем, какие поля могут быть заполнены
    protected $fillable = ['name'];

    /**
     * Связь один-ко-многим с моделью Item
     */
    public function items()
    {
        return $this->belongsToMany(Item::class, 'category_item');
    }
}
