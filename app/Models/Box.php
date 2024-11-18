<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Box extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'contents', 'price'];

    public function items()
    {
        return $this->belongsToMany(Item::class, 'box_product')->withPivot('quantity');
    }
}
