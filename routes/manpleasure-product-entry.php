<?php

use App\Http\Controllers\Admin\ProductEntryController;
use App\Http\Controllers\Admin\ProductPricingController;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance;

Route::middleware([
    'web',
    PreventRequestsDuringMaintenance::class,
    'admin',
    NoCacheMiddleware::class,
])->group(function () {
    Route::prefix(config('app.admin_url').'/catalog/product-entry')
        ->name('admin.catalog.product_entry.')
        ->controller(ProductEntryController::class)
        ->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('suggest-sku', 'suggestSku')->name('suggest_sku');
            Route::post('', 'store')->name('store');
        });

    Route::prefix(config('app.admin_url').'/catalog/product-pricing')
        ->name('admin.catalog.product_pricing.')
        ->controller(ProductPricingController::class)
        ->group(function () {
            Route::get('{id}', 'show')->whereNumber('id')->name('show');
            Route::put('{id}', 'update')->whereNumber('id')->name('update');
        });
});
