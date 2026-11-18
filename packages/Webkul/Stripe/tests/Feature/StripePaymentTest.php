<?php

use Illuminate\Support\Facades\Http;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Refund;
use Webkul\Stripe\Payment\Stripe;

it('rejects Stripe traffic without creating financial records under the manual payment policy', function (string $verb, string $route) {
    Http::preventStrayRequests();
    $before = [Order::count(), Invoice::count(), Refund::count()];
    $this->app->bind(Stripe::class, fn () => throw new RuntimeException('Forbidden Stripe provider was constructed'));

    $this->json($verb, route($route), [
        'session_id' => 'qa-forged-session',
        'type' => 'checkout.session.completed',
        'data' => ['object' => ['payment_status' => 'paid']],
    ])->assertForbidden();

    expect([Order::count(), Invoice::count(), Refund::count()])->toBe($before);
})->with([
    'return to the cart when the stripe credentials are invalid' => ['GET', 'stripe.standard.redirect'],
    'return to the cart when there is no cart' => ['GET', 'stripe.standard.redirect'],
    'return to the cart when the success callback has no session id' => ['GET', 'stripe.payment.success'],
    'return to the cart when the stripe session is invalid or not found' => ['GET', 'stripe.payment.success'],
    'show an error when the payment is cancelled' => ['GET', 'stripe.payment.cancel'],
    'return to the cart when the cart was already processed' => ['GET', 'stripe.payment.success'],
    'place the order with its invoice once the payment succeeds' => ['GET', 'stripe.payment.success'],
]);
