<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStockBatch extends Model
{
    protected $fillable = ['product_id', 'inventory_source_id', 'quantity', 'ship_from', 'ship_to'];

    protected $casts = [
        'ship_from' => 'date',
        'ship_to' => 'date',
    ];
}
