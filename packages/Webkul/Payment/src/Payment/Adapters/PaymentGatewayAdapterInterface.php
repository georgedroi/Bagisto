<?php

namespace Webkul\Payment\Payment\Adapters;

use Illuminate\Http\Request;
use Webkul\Sales\Contracts\Order;

interface PaymentGatewayAdapterInterface
{
    /**
     * Get adapter identifier.
     */
    public function getIdentifier(): string;

    /**
     * Check if the gateway is configured for the given method.
     */
    public function isConfigured(string $methodCode): bool;

    /**
     * Initiate payment redirect or checkout session.
     */
    public function initiatePayment(Order $order, string $methodCode): array;

    /**
     * Verify incoming webhook request signature.
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Process webhook payload into standardized data structure.
     */
    public function processWebhookPayload(array $payload): array;
}
