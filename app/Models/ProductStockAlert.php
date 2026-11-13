<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStockAlert extends Model
{
    protected $fillable = ['product_id', 'threshold'];
}
