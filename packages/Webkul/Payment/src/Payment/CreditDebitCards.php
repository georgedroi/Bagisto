<?php

namespace Webkul\Payment\Payment;

use Illuminate\Support\Facades\Storage;

class CreditDebitCards extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'creditdebitcards';

    /**
     * Checks if payment method is available.
     */
    public function isAvailable(): bool
    {
        return false;
    }

    /**
     * Get redirect url.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return route('payment.creditdebitcards.redirect');
    }

    /**
     * Get payment method image.
     *
     * @return string
     */
    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : bagisto_asset('images/credit-debit-cards.png', 'shop');
    }
}
