<?php

use Illuminate\Support\Facades\Http;
use Webkul\Core\Models\CoreConfig;
use Webkul\PayU\Payment\PayU;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderTransaction;

beforeEach(function () {
    foreach (['active' => '1', 'sandbox' => '1', 'merchant_key' => 'test_merchant_key', 'merchant_salt' => 'test_merchant_salt'] as $code => $value) {
        CoreConfig::factory()->create([
            'code' => 'sales.payment_methods.payu.'.$code,
            'value' => $value,
            'channel_code' => 'default',
        ]);
    }
});

it('rejects PayU traffic for a live PayU cart without creating financial records under the manual payment policy', function (string $verb, string $route, array $payload) {
    Http::preventStrayRequests();
    $cart = $this->createCartWithItems('payu', ['base_currency_code' => 'INR']);
    $before = [Order::count(), Invoice::count(), OrderTransaction::count()];
    $this->app->bind(PayU::class, fn () => throw new RuntimeException('Forbidden PayU provider was constructed'));

    $this->json($verb, route($route), array_replace($payload, ['cart_id' => $cart->id]))->assertForbidden();

    expect([Order::count(), Invoice::count(), OrderTransaction::count()])->toBe($before)
        ->and($cart->fresh()->is_active)->toBeTruthy();
})->with([
    'start a payment with valid credentials' => ['GET', 'payu.redirect', []],
    'start a payment with an empty salt' => ['GET', 'payu.redirect', ['merchant_salt' => '']],
    'report a successful payment with a matching hash' => ['POST', 'payu.success', ['txnid' => 'PAYU_TEST123', 'mihpayid' => 'MIHPAY_TEST_456', 'status' => 'success', 'hash' => 'valid_hash_value']],
    'report a successful payment with a forged hash' => ['POST', 'payu.success', ['txnid' => 'PAYU_TEST123', 'status' => 'success', 'hash' => 'forged']],
    'report a failed payment' => ['POST', 'payu.failure', ['txnid' => 'PAYU_TEST123', 'status' => 'failure']],
    'report a cancelled payment' => ['POST', 'payu.cancel', ['txnid' => 'PAYU_TEST123', 'status' => 'cancel']],
]);
