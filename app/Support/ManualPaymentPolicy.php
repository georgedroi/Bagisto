<?php

namespace App\Support;

use Webkul\Payment\Payment\Payment;

/**
 * Share the approved manual methods and fail closed on unapproved runtime methods at launch.
 */
class ManualPaymentPolicy
{
    /**
     * Manual payment methods permitted by the store owner: the same two that checkout offers (QR Code and Bank Transfer).
     */
    public const METHODS = ['banktransfer', 'duitnowqr_manual'];

    /**
     * Check every registered gateway and require an available approved payment option.
     */
    public static function readinessChecks(): array
    {
        $checks = [];
        $hasManualMethod = false;

        foreach ((array) config('payment_methods', []) as $code => $method) {
            $class = $method['class'] ?? null;
            $validClass = is_string($class) && is_subclass_of($class, Payment::class);

            if (in_array($code, self::METHODS, true)) {
                $hasManualMethod = $hasManualMethod || ($validClass && app($class)->isAvailable());

                continue;
            }

            $checks['unverified payment method disabled: '.$code] = ! core()->getConfigData('sales.payment_methods.'.$code.'.active');
            $checks['unverified payment method unavailable: '.$code] = $validClass && ! app($class)->isAvailable();
        }

        $checks['approved manual payment method available'] = $hasManualMethod;

        return $checks;
    }
}
