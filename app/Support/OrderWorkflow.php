<?php

namespace App\Support;

use Webkul\Payment\Repositories\PaymentReceiptRepository;
use Webkul\Sales\Contracts\Order as OrderContract;
use Webkul\Sales\Models\Order;

class OrderWorkflow
{
    /**
     * Operational steps, separate from Bagisto's invoice and shipment status.
     */
    public const TRANSITIONS = [
        'confirmed' => ['packing'],
        'packing' => ['ready_to_ship'],
        'ready_to_ship' => ['handed_to_courier'],
        'handed_to_courier' => ['in_transit'],
        'in_transit' => ['out_for_delivery', 'failed_to_deliver'],
        'out_for_delivery' => ['delivered', 'failed_to_deliver'],
        'failed_to_deliver' => ['in_transit', 'out_for_delivery', 'delivered'],
    ];

    /**
     * Resolve terminal order states before the saved operational stage.
     */
    public static function current(OrderContract $order): string
    {
        return match ($order->status) {
            Order::STATUS_CANCELED => 'cancelled',
            Order::STATUS_CLOSED => 'to-return-refund',
            Order::STATUS_FRAUD => 'on-hold',
            default => $order->workflow_status ?: self::inferred($order),
        };
    }

    /**
     * Infer the stage of an order saved before the workflow from its invoices, shipments and latest receipt.
     */
    protected static function inferred(OrderContract $order): string
    {
        if ($order->base_grand_total_invoiced > 0 && $order->base_total_due <= 0.01) {
            return $order->shipments->isNotEmpty() ? 'handed_to_courier' : 'confirmed';
        }

        return match (app(PaymentReceiptRepository::class)->latestStatus($order->id)) {
            'pending' => 'awaiting_verification',
            'rejected' => 'payment_clarification',
            'approved' => 'confirmed',
            default => 'awaiting_payment',
        };
    }

    /**
     * List the permitted next steps for the current order.
     */
    public static function next(OrderContract $order): array
    {
        return self::TRANSITIONS[self::current($order)] ?? [];
    }

    /**
     * Determine whether payment verification has unlocked fulfillment.
     */
    public static function paymentVerified(OrderContract $order): bool
    {
        return in_array(self::current($order), [...array_keys(self::TRANSITIONS), 'delivered'], true);
    }
}
