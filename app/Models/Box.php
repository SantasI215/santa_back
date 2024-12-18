<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Box extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'is_official', 'active', 'image'];

    /**
     * Связь многие-ко-многим с категориями.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'box_category', 'box_id', 'category_id');
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? Storage::url($this->image) : null;
    }

    protected $appends = ['image_url'];
}
