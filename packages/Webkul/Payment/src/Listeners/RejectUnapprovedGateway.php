<?php

namespace Webkul\Payment\Listeners;

use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route;
use Webkul\Payment\PaymentPolicy;

class RejectUnapprovedGateway
{
    /**
     * Gateway packages whose routes, callbacks and webhooks no longer take payments.
     */
    protected const PACKAGES = ['Stripe', 'Paypal', 'Razorpay', 'PayU', 'PayGlocal', 'PhonePe'];

    /**
     * Payment package controllers that only serve the disabled automated methods.
     */
    protected const CONTROLLERS = ['PaymentRedirectController', 'WebhookController'];

    /**
     * Stop gateway routes before their controller is built, also in the kernel's terminate phase.
     */
    public function handle(RouteMatched $event): void
    {
        if (! $event->route->getAction('payment_policy_rejected') && ! $this->isGatewayRoute($event->route)) {
            return;
        }

        $event->route->uses(fn () => PaymentPolicy::reject())->setAction([...$event->route->getAction(), 'payment_policy_rejected' => true]);

        PaymentPolicy::reject();
    }

    /**
     * Whether the route belongs to a disabled payment gateway.
     */
    protected function isGatewayRoute(Route $route): bool
    {
        $action = $route->getActionName();

        if (! is_string($action)) {
            return false;
        }

        foreach (self::PACKAGES as $package) {
            if (str_starts_with($action, 'Webkul\\'.$package.'\\')) {
                return true;
            }
        }

        foreach (self::CONTROLLERS as $controller) {
            if (str_starts_with($action, 'Webkul\\Payment\\Http\\Controllers\\'.$controller.'@')) {
                return true;
            }
        }

        return false;
    }
}
