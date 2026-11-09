<?php

use App\Http\Controllers\Admin\SellerWorkspaceController;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance;

Route::middleware([
    'web',
    PreventRequestsDuringMaintenance::class,
    'admin',
    NoCacheMiddleware::class,
])->group(function () {
    Route::get(config('app.admin_url').'/workspace', [SellerWorkspaceController::class, 'overview'])->name('admin.seller_workspace.home');

    Route::controller(SellerWorkspaceController::class)->prefix(config('app.admin_url'))->group(function () {
        Route::get('overview', 'overview')->name('admin.seller_workspace.overview');
        Route::get('orders', 'orders')->name('admin.seller_workspace.orders');
        Route::get('shipping', 'shipping')->name('admin.seller_workspace.shipping');
        Route::get('returns', 'returns')->name('admin.seller_workspace.returns');
        Route::get('reviews', 'reviews')->name('admin.seller_workspace.reviews');
        Route::get('customer-list', 'customers')->name('admin.seller_workspace.customers');
        Route::get('products', 'products')->name('admin.seller_workspace.products');
        Route::get('products/add', 'addProduct')->name('admin.seller_workspace.add_product');
        Route::get('products/{id}/edit', 'editProduct')->whereNumber('id')->name('admin.seller_workspace.edit_product');
        Route::post('products/{id}/lifecycle', 'saveLifecycle')->whereNumber('id')->name('admin.seller_workspace.product_lifecycle');
        Route::get('promotions', 'promotions')->name('admin.seller_workspace.promotions');
        Route::get('income', 'income')->name('admin.seller_workspace.income');
        Route::get('refunds', 'refunds')->name('admin.seller_workspace.refunds');
        Route::get('fees', 'fees')->name('admin.seller_workspace.fees');
        Route::get('settings', 'settings')->name('admin.seller_workspace.settings');
    });

    Route::prefix(config('app.admin_url').'/seller-center')->group(function () {
        Route::redirect('', '/'.config('app.admin_url').'/overview')->name('admin.seller_center.index');
        Route::redirect('products', '/'.config('app.admin_url').'/products')->name('admin.seller_center.products');
        Route::redirect('products/add', '/'.config('app.admin_url').'/products/add')->name('admin.seller_center.add_product');
        Route::redirect('products/{id}/edit', '/'.config('app.admin_url').'/products/{id}/edit')->whereNumber('id')->name('admin.seller_center.edit_product');
    });
});
