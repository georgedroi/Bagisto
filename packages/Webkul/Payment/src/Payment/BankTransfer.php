<?php

namespace Webkul\Payment\Payment;

use Illuminate\Support\Facades\Storage;

class BankTransfer extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'banktransfer';

    /**
     * Get redirect url.
     *
     * @return string|null
     */
    public function getRedirectUrl() {}

    /**
     * Returns the bank account details and instructions as a single title and value pair.
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
            'title' => trans('payment::app.banktransfer-details'),
            'value' => implode("\n", $lines),
        ];
    }

    /**
     * Checks if payment method is available. A method without an account number cannot take payment.
     */
    public function isAvailable(): bool
    {
        return (bool) $this->getConfigData('active') && ! empty(trim((string) $this->getConfigData('account_number')));
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
