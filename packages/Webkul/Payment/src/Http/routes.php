<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Webkul\Payment\Http\Controllers\Admin\ReceiptController as AdminReceiptController;
use Webkul\Payment\Http\Controllers\Customer\ReceiptController as CustomerReceiptController;
use Webkul\Payment\Http\Controllers\PaymentRedirectController;
use Webkul\Payment\Http\Controllers\WebhookController;

Route::group(['middleware' => ['web']], function () {
    Route::post('payment/webhook/{provider?}', [WebhookController::class, 'handle'])
        ->withoutMiddleware([ValidateCsrfToken::class])
        ->name('payment.webhook');

    Route::get('payment/creditdebitcards/redirect', [PaymentRedirectController::class, 'creditdebitcards'])
        ->name('payment.creditdebitcards.redirect');

    Route::get('payment/fpx/redirect', [PaymentRedirectController::class, 'fpx'])
        ->name('payment.fpx.redirect');

    Route::get('payment/duitnowqr_auto/redirect', [PaymentRedirectController::class, 'duitnowqr_auto'])
        ->name('payment.duitnowqr_auto.redirect');

    Route::get('payment/tngewallet/redirect', [PaymentRedirectController::class, 'tngewallet'])
        ->name('payment.tngewallet.redirect');

    Route::group(['middleware' => ['customer']], function () {
        Route::post('customer/account/orders/{id}/receipt', [CustomerReceiptController::class, 'upload'])
            ->name('customer.orders.receipt.upload');
    });

    Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url', 'admin')], function () {
        Route::post('sales/receipts/{id}/approve', [AdminReceiptController::class, 'approve'])
            ->name('admin.sales.receipts.approve');

        Route::post('sales/receipts/{id}/reject', [AdminReceiptController::class, 'reject'])
            ->name('admin.sales.receipts.reject');
    });
});
