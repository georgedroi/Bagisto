<?php

namespace Webkul\Product\Observers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Contracts\Product;

class ProductObserver
{
    /**
     * Handle the Product "deleted" event.
     *
     * @param  Product  $product
     * @return void
     */
    public function deleted($product)
    {
        DB::afterCommit(fn () => Storage::deleteDirectory('product/'.$product->id));
    }
}
