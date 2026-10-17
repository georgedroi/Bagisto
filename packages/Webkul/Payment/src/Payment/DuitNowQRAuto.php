<?php

namespace Webkul\Payment\Payment;

use Illuminate\Support\Facades\Storage;

class DuitNowQRAuto extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'duitnowqr_auto';

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
        return route('payment.duitnowqr_auto.redirect');
    }

    /**
     * Get payment method image.
     *
     * @return string
     */
    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : bagisto_asset('images/duitnow-qr.png', 'shop');
    }
}
