<?php

namespace Webkul\Payment\Payment\Adapters;

use Illuminate\Http\Request;
use Webkul\Sales\Contracts\Order;

class DefaultPaymentGatewayAdapter implements PaymentGatewayAdapterInterface
{
    /**
     * Get adapter identifier.
     */
    public function getIdentifier(): string
    {
        return 'default';
    }

    /**
     * Check if the gateway is configured for the given method.
     */
    public function isConfigured(string $methodCode): bool
    {
        return false;
    }

    /**
     * Initiate payment redirect or checkout session.
     */
    public function initiatePayment(Order $order, string $methodCode): array
    {
        return [
            'success' => false,
            'redirect_url' => null,
        ];
    }

    /**
     * Verify incoming webhook request signature.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        return false;
    }

    /**
     * Process webhook payload into standardized data structure.
     */
    public function processWebhookPayload(array $payload): array
    {
        return [
            'order_id' => $payload['order_id'] ?? null,
            'amount' => (float) ($payload['amount'] ?? 0),
            'currency' => $payload['currency'] ?? 'MYR',
            'status' => $payload['status'] ?? 'pending',
            'transaction_id' => $payload['transaction_id'] ?? null,
        ];
    }
}
