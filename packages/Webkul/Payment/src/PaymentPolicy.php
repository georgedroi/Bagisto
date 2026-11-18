<?php

namespace Webkul\Payment;

class PaymentPolicy
{
    /**
     * The owner's approved methods for new purchases and manual verification: QR code and bank transfer.
     */
    public static function allows(mixed $method): bool
    {
        return is_string($method) && in_array($method, ['duitnowqr_manual', 'banktransfer'], true);
    }

    /**
     * Reject unsupported methods before payment side effects or order persistence, as a 403 in every debug mode.
     */
    public static function reject(): never
    {
        abort(403, trans('shop::app.checkout.cart.specify-payment-method'));
    }
}
