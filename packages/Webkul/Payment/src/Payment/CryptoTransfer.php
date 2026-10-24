<?php

namespace Webkul\Payment\Payment;

use Illuminate\Support\Facades\Storage;

class CryptoTransfer extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'cryptotransfer';

    /**
     * Get redirect url.
     *
     * @return string|null
     */
    public function getRedirectUrl() {}

    /**
     * Returns the configured wallet addresses and instructions as a single title and value pair.
     *
     * @return array
     */
    public function getAdditionalDetails()
    {
        $lines = [];

        foreach ($this->getWallets() as $label => $address) {
            $lines[] = $label.': '.$address;
        }

        if ($instructions = $this->getConfigData('instructions')) {
            $lines[] = $instructions;
        }

        if (empty($lines)) {
            return [];
        }

        return [
            'title' => trans('payment::app.cryptotransfer-details'),
            'value' => implode("\n", $lines),
        ];
    }

    /**
     * Get the wallet addresses that have been configured, keyed by their display label.
     */
    public function getWallets(): array
    {
        $wallets = [
            trans('payment::app.cryptotransfer-btc') => $this->getConfigData('btc_address'),
            trans('payment::app.cryptotransfer-usdt-trc20') => $this->getConfigData('usdt_trc20_address'),
            trans('payment::app.cryptotransfer-usdt-erc20') => $this->getConfigData('usdt_erc20_address'),
        ];

        return array_filter(array_map('trim', array_map('strval', $wallets)));
    }

    /**
     * Checks if payment method is available. A method without any wallet address cannot take payment.
     */
    public function isAvailable(): bool
    {
        return (bool) $this->getConfigData('active') && ! empty($this->getWallets());
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
