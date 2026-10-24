<?php

namespace App\Support;

use Webkul\Sales\Contracts\Order;

class WhatsAppMessage
{
    public const RECEIVED = 'received';

    public const PAID = 'paid';

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
        $phone = static::phoneFor($order);

        if (! $phone) {
            return null;
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode(static::messageFor($order, $type));
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
