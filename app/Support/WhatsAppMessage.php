<?php

namespace App\Support;

use SimpleSoftwareIO\QrCode\Generator;
use Webkul\Sales\Contracts\Order;

/**
 * Build discreet manual WhatsApp handoffs with payment and shipment guards.
 */
class WhatsAppMessage
{
    /**
     * Supported administrator message types.
     */
    public const RECEIVED = 'received';

    /**
     * Payment confirmation message.
     */
    public const PAID = 'paid';

    /**
     * Shipment confirmation message.
     */
    public const SHIPPED = 'shipped';

    /**
     * Get the customer's phone number as digits with a country code, or null when none can be used.
     */
    public static function phoneFor(Order $order): ?string
    {
        $address = $order->billing_address ?: $order->shipping_address;

        if (! $address || blank($address->phone)) {
            return null;
        }

        $raw = trim((string) $address->phone);

        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($raw, '+')) {
            return $digits;
        }

        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }

        if (strtoupper((string) $address->country) === 'MY' && str_starts_with($digits, '0')) {
            return '60'.substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Build a wa.me link that opens WhatsApp with the message already written for the given order.
     */
    public static function linkFor(Order $order, string $type): ?string
    {
        if (! in_array($type, [static::RECEIVED, static::PAID, static::SHIPPED], true)
            || ($type === static::PAID && ! static::paymentReceived($order))
            || ($type === static::SHIPPED && ! static::isShipped($order))) {
            return null;
        }

        $phone = static::phoneFor($order);

        if (! $phone) {
            return null;
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode(static::messageFor($order, $type));
    }

    /**
     * Prepare a customer receipt handoff without product names or automatic sending.
     */
    public static function proofLinkFor(Order $order): ?string
    {
        $phone = config('manpleasure-contact.whatsapp');

        if (! is_string($phone) || ! preg_match('/\A[1-9][0-9]{8,14}\z/', $phone)
            || ! in_array($order->payment?->method, ManualPaymentPolicy::METHODS, true)) {
            return null;
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode(trans('payment::app.whatsapp-proof-message', ['order' => '#'.$order->increment_id]));
    }

    /**
     * Render the handoff as a local QR image using the installed QR encoder.
     */
    public static function proofQrFor(Order $order): ?string
    {
        $link = static::proofLinkFor($order);

        if (! $link) {
            return null;
        }

        $svg = (new Generator)->format('svg')->size(240)->margin(4)->errorCorrection('M')->generate($link);

        return 'data:image/svg+xml;base64,'.base64_encode((string) $svg);
    }

    /**
     * Require verified manual payment or a fully invoiced standard order before messaging.
     */
    public static function paymentReceived(Order $order): bool
    {
        if (in_array($order->status, ['canceled', 'closed', 'fraud'], true)) {
            return false;
        }

        return OrderWorkflow::supports($order)
            ? OrderWorkflow::paymentVerified($order)
            : $order->base_grand_total_invoiced > 0 && $order->base_total_due <= 0.01;
    }

    /**
     * Build the message text. It never names the products that were bought.
     */
    public static function messageFor(Order $order, string $type): string
    {
        $parameters = [
            'name' => trim((string) $order->customer_first_name) ?: trans('admin::whatsapp.customer'),
            'order' => '#'.$order->increment_id,
            'store' => trans('admin::whatsapp.store'),
        ];

        if ($type === static::SHIPPED) {
            $shipment = $order->shipments->sortByDesc('id')->first();

            $parameters['carrier'] = $shipment?->carrier_title ?: trans('admin::whatsapp.carrier');

            $tracking = trim((string) $shipment?->track_number);

            return $tracking !== ''
                ? trans('admin::whatsapp.message-shipped-tracking', $parameters + ['tracking' => $tracking])
                : trans('admin::whatsapp.message-shipped', $parameters);
        }

        return trans('admin::whatsapp.message-'.$type, $parameters);
    }

    /**
     * Whether the order has been shipped at least once.
     */
    public static function isShipped(Order $order): bool
    {
        return $order->shipments->isNotEmpty();
    }
}
