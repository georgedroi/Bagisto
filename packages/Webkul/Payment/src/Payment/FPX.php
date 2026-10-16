<?php

namespace Webkul\Payment\Payment;

use Illuminate\Support\Facades\Storage;

class FPX extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'fpx';

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
        return route('payment.fpx.redirect');
    }

    /**
     * Get payment method image.
     *
     * @return string
     */
    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : bagisto_asset('images/fpx.png', 'shop');
    }
}
