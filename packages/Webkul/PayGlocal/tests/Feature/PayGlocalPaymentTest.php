<?php

use Illuminate\Support\Facades\Http;
use Webkul\Core\Models\CoreConfig;
use Webkul\PayGlocal\Payment\PayGlocal;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderTransaction;

beforeEach(function () {
    foreach (['active' => '1', 'sandbox' => '1', 'merchant_id' => 'qa-merchant', 'public_key_id' => 'qa-public', 'private_key_id' => 'qa-private'] as $code => $value) {
        CoreConfig::factory()->create([
            'code' => 'sales.payment_methods.payglocal.'.$code,
            'value' => $value,
            'channel_code' => 'default',
        ]);
    }
});

it('rejects PayGlocal traffic for a live PayGlocal cart without creating financial records under the manual payment policy', function (string $verb, string $route, array $payload) {
    Http::preventStrayRequests();
    $cart = $this->createCartWithItems('payglocal');
    $before = [Order::count(), Invoice::count(), OrderTransaction::count()];
    $this->app->bind(PayGlocal::class, fn () => throw new RuntimeException('Forbidden PayGlocal provider was constructed'));

    $this->json($verb, route($route), array_replace($payload, ['cart_id' => $cart->id]))->assertForbidden();

    expect([Order::count(), Invoice::count(), OrderTransaction::count()])->toBe($before)
        ->and($cart->fresh()->is_active)->toBeTruthy();
})->with([
    'start a payment' => ['GET', 'payglocal.redirect', []],
    'settle a payment from a signed-looking callback' => ['POST', 'payglocal.callback', ['x-gl-token' => 'token']],
    'settle a payment from a forged callback' => ['POST', 'payglocal.callback', ['x-gl-token' => 'forged']],
    'receive a callback without a token' => ['POST', 'payglocal.callback', []],
    'show the order for a typed reference' => ['GET', 'payglocal.success', ['merchantTxnId' => 'PGL1TTEST']],
    'place an order from a webhook' => ['POST', 'payglocal.webhook', ['x-gl-token' => 'token']],
    'place an order from a forged webhook' => ['POST', 'payglocal.webhook', ['x-gl-token' => 'forged']],
]);
