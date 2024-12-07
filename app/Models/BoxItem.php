<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoxItem extends Model
{
    protected $fillable = [
        'box_id',
        'item_id',
        'quantity',
        'status'
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'box_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
