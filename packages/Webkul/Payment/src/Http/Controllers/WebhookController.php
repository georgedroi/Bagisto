<?php

namespace Webkul\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Payment\Payment\Adapters\PaymentGatewayAdapterInterface;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;

class WebhookController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected PaymentGatewayAdapterInterface $gatewayAdapter,
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository
    ) {}

    /**
     * Handle incoming payment webhook.
     */
    public function handle(Request $request, ?string $provider = null): JsonResponse
    {
        if (! $provider || ! $this->gatewayAdapter->isConfigured($provider)) {
            return response()->json(['status' => 'unavailable'], 503);
        }

        if (! $this->gatewayAdapter->verifyWebhookSignature($request)) {
            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        $payload = $this->gatewayAdapter->processWebhookPayload($request->all());

        if (empty($payload['order_id'])) {
            return response()->json(['error' => 'Missing order ID'], 400);
        }

        $order = $this->orderRepository->find($payload['order_id']);

        if (! $order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($order->order_currency_code !== $payload['currency']) {
            return response()->json(['error' => 'Currency mismatch'], 422);
        }

        if (abs((float) $order->grand_total - (float) $payload['amount']) > 0.01) {
            return response()->json(['error' => 'Amount mismatch'], 422);
        }

        if ($payload['status'] !== 'paid' && $payload['status'] !== 'completed') {
            return response()->json(['status' => 'acknowledged']);
        }

        if (! $order->canInvoice()) {
            return response()->json([
                'status' => 'already_processed',
                'message' => 'Order is already invoiced',
            ]);
        }

        $invoiceData = ['order_id' => $order->id];

        foreach ($order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        $this->invoiceRepository->create($invoiceData);

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice created successfully',
        ]);
    }
}
