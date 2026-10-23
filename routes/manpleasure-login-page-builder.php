<?php

use App\Http\Controllers\Admin\LoginPageBuilderController;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance;

Route::middleware([
    'web',
    PreventRequestsDuringMaintenance::class,
    'admin',
    NoCacheMiddleware::class,
])
    ->prefix(config('app.admin_url') . '/appearance/login-page-builder')
    ->name('admin.appearance.login_page_builder.')
    ->controller(LoginPageBuilderController::class)
    ->group(function () {
        Route::get('', 'index')->name('index');
        Route::post('save', 'save')->name('save');
        Route::post('publish', 'publish')->name('publish');
        Route::post('unpublish', 'unpublish')->name('unpublish');
        Route::get('status', 'status')->name('status');
    });