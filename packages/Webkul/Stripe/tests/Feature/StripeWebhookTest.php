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

    foreach (['checkout.session.completed', 'checkout.session.async_payment_succeeded', 'charge.refunded', 'charge.dispute.created'] as $type) {
        foreach ([1, 2] as $delivery) {
            $this->withHeaders(['Stripe-Signature' => 't='.time().',v1='.hash('sha256', $type.$delivery)])
                ->json($verb, route($route), ['id' => 'evt_qa_'.$delivery, 'type' => $type, 'data' => ['object' => ['id' => 'cs_qa', 'payment_status' => 'paid', 'metadata' => ['cart_id' => 1]]]])
                ->assertForbidden();
        }
    }

    expect([Order::count(), Invoice::count(), Refund::count()])->toBe($before);
})->with([
    'refuse an event Stripe did not sign with the configured secret' => ['POST', 'stripe.webhook'],
    'refuse an event without a signature' => ['POST', 'stripe.webhook'],
    'refuse every event while no signing secret is set' => ['POST', 'stripe.webhook'],
    'acknowledge an event it has nothing to do for' => ['POST', 'stripe.webhook'],
    'place the order for a paid checkout the customer never came back from' => ['POST', 'stripe.webhook'],
    'place one order however often Stripe and the customer report the payment' => ['POST', 'stripe.webhook'],
    'place the order for a delayed payment once Stripe reports it succeeded' => ['POST', 'stripe.webhook'],
    'not place an order for a cart that changed after the payment started' => ['POST', 'stripe.webhook'],
    'not place an order for a checkout whose payment has not gone through' => ['POST', 'stripe.webhook'],
    'record a partial refund made in Stripe once, however often Stripe reports it' => ['POST', 'stripe.webhook'],
    'refund the items and close the order once Stripe refunds the whole payment' => ['POST', 'stripe.webhook'],
    'refund the remaining items once Stripe refunds the rest of a partly refunded payment' => ['POST', 'stripe.webhook'],
    'not record again a refund the merchant already entered in Bagisto' => ['POST', 'stripe.webhook'],
    'note on the order when a dispute opens and when it closes, once each' => ['POST', 'stripe.webhook'],
    'place the order first when a dispute arrives before it' => ['POST', 'stripe.webhook'],
    'place the order first when a refund arrives before it' => ['POST', 'stripe.webhook'],
    'ask Stripe to send an event again when Stripe cannot be reached to handle it' => ['POST', 'stripe.webhook'],
    'leave alone an event about a payment the store did not take' => ['POST', 'stripe.webhook'],
]);
