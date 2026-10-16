<?php

use App\Http\Controllers\Admin\SystemUpdateController;
use App\Http\Middleware\RequireUpdateOwner;
use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance;

Route::middleware(['web', PreventRequestsDuringMaintenance::class, 'admin', NoCacheMiddleware::class, RequireUpdateOwner::class])
    ->prefix(config('app.admin_url').'/settings/system-updates')
    ->name('admin.settings.system_updates.')
    ->controller(SystemUpdateController::class)
    ->group(function () {
        Route::get('', 'index')->name('index');
        Route::post('check', 'check')->middleware('throttle:10,1')->name('check');
        Route::post('install', 'install')->middleware('throttle:2,1')->name('install');
        Route::post('rollback', 'rollback')->middleware('throttle:2,1')->name('rollback');
        Route::get('operations/{operation}', 'status')->whereUuid('operation')->name('status');
    });
