<?php

use App\Http\Controllers\Admin\OrderWorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'admin'])
    ->prefix(config('app.admin_url', 'admin').'/sales/orders')
    ->name('admin.sales.orders.')
    ->where(['orderId' => '[0-9]+'])
    ->group(function () {
        Route::post('{orderId}/workflow', [OrderWorkflowController::class, 'update'])->name('workflow.update');
        Route::post('{orderId}/awb', [OrderWorkflowController::class, 'uploadAwb'])->name('awb.upload');
        Route::get('{orderId}/awb/print', [OrderWorkflowController::class, 'printAwb'])->name('awb.print');
        Route::get('{orderId}/awb/image', [OrderWorkflowController::class, 'awbImage'])->name('awb.image');
    });
