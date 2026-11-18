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
    'send the customer to Stripe to pay for their cart' => ['GET', 'stripe.standard.redirect'],
    'bring the customer back to the cart when Stripe cannot start the payment' => ['GET', 'stripe.standard.redirect'],
    'tell the customer when their cart changed after the payment started' => ['GET', 'stripe.payment.success'],
]);
