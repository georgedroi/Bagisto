<?php

namespace Webkul\Payment\Payment;

use Illuminate\Support\Facades\Storage;

class DuitNowQRManual extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'duitnowqr_manual';

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
     * @return string|null
     */
    public function getRedirectUrl() {}

    /**
     * Returns payment method additional information.
     *
     * @return array
     */
    public function getAdditionalDetails()
    {
        $details = [];

        if ($bankName = $this->getConfigData('bank_name')) {
            $details[] = [
                'title' => trans('payment::app.bank-name'),
                'value' => $bankName,
            ];
        }

        if ($accountName = $this->getConfigData('account_name')) {
            $details[] = [
                'title' => trans('payment::app.account-name'),
                'value' => $accountName,
            ];
        }

        if ($accountNumber = $this->getConfigData('account_number')) {
            $details[] = [
                'title' => trans('payment::app.account-number'),
                'value' => $accountNumber,
            ];
        }

        if ($instructions = $this->getConfigData('instructions')) {
            $details[] = [
                'title' => trans('payment::app.instructions'),
                'value' => $instructions,
            ];
        }

        return $details;
    }

    /**
     * Get payment method QR image.
     */
    public function getQrImage(): ?string
    {
        $url = $this->getConfigData('qr_image');

        return $url ? Storage::url($url) : null;
    }

    /**
     * Get payment method logo image.
     *
     * @return string
     */
    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : bagisto_asset('images/duitnow-qr.png', 'shop');
    }
}
