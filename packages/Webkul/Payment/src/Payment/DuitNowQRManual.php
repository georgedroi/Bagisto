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
     * Require a QR image before offering a scan-to-pay method.
     */
    public function isAvailable(): bool
    {
        return (bool) $this->getConfigData('active')
            && trim((string) $this->getConfigData('qr_image')) !== '';
    }

    /**
     * Get redirect url.
     *
     * @return string|null
     */
    public function getRedirectUrl() {}

    /**
     * Returns the DuitNow account details and instructions as a single title and value pair.
     *
     * @return array
     */
    public function getAdditionalDetails()
    {
        $lines = [];

        $fields = [
            'payment::app.bank-name' => $this->getConfigData('bank_name'),
            'payment::app.account-name' => $this->getConfigData('account_name'),
            'payment::app.account-number' => $this->getConfigData('account_number'),
        ];

        foreach ($fields as $label => $value) {
            if ($value = trim((string) $value)) {
                $lines[] = trans($label).': '.$value;
            }
        }

        if ($instructions = $this->getConfigData('instructions')) {
            $lines[] = $instructions;
        }

        if (empty($lines)) {
            return [];
        }

        return [
            'title' => trans('payment::app.duitnowqr-details'),
            'value' => implode("\n", $lines),
        ];
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

        return $url ? Storage::url($url) : bagisto_asset('images/money-transfer.png', 'shop');
    }
}
