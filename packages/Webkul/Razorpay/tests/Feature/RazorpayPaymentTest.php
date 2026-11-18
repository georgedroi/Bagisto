<?php

use Illuminate\Support\Facades\Http;
use Webkul\Core\Models\CoreConfig;
use Webkul\Razorpay\Payment\RazorpayPayment;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderTransaction;

beforeEach(function () {
    foreach (['active' => '1', 'sandbox' => '1', 'test_client_id' => 'rzp_test_qa', 'test_client_secret' => 'qa_secret'] as $code => $value) {
        CoreConfig::factory()->create([
            'code' => 'sales.payment_methods.razorpay.'.$code,
            'value' => $value,
            'channel_code' => 'default',
        ]);
    }
});

it('rejects Razorpay traffic for a live Razorpay cart without creating financial records under the manual payment policy', function (string $verb, string $route, array $payload) {
    Http::preventStrayRequests();
    $cart = $this->createCartWithItems('razorpay', ['base_currency_code' => 'INR']);
    $before = [Order::count(), Invoice::count(), OrderTransaction::count()];
    $this->app->bind(RazorpayPayment::class, fn () => throw new RuntimeException('Forbidden Razorpay provider was constructed'));

    $this->json($verb, route($route), array_replace($payload, ['cart_id' => $cart->id]))->assertForbidden();

    expect([Order::count(), Invoice::count(), OrderTransaction::count()])->toBe($before)
        ->and($cart->fresh()->is_active)->toBeTruthy();
})->with([
    'start a payment' => ['GET', 'razorpay.payment.redirect', []],
    'report a successful payment with a valid-looking signature' => ['GET', 'razorpay.payment.success', ['razorpay_payment_id' => 'pay_QA', 'razorpay_order_id' => 'order_QA', 'razorpay_signature' => 'valid']],
    'report a successful payment with a forged signature' => ['GET', 'razorpay.payment.success', ['razorpay_payment_id' => 'pay_QA', 'razorpay_order_id' => 'order_QA', 'razorpay_signature' => 'forged']],
    'report a failed payment' => ['GET', 'razorpay.payment.cancel', ['error' => 'payment_failed']],
]);
