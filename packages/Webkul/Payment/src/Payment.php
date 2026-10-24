<?php

namespace Webkul\Payment;

use Illuminate\Support\Facades\Config;
use Webkul\Checkout\Contracts\Cart;

class Payment
{
    /**
     * Returns all supported payment methods
     *
     * @return array
     */
    public function getSupportedPaymentMethods()
    {
        return [
            'payment_methods' => $this->getPaymentMethods(),
        ];
    }

    /**
     * Returns all supported payment methods
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        $paymentMethods = [];

        foreach (Config::get('payment_methods') as $paymentMethodConfig) {
            $paymentMethod = app($paymentMethodConfig['class']);

            if ($paymentMethod->isAvailable()) {
                $paymentMethods[] = [
                    'method' => $paymentMethod->getCode(),
                    'method_title' => $paymentMethod->getTitle(),
                    'description' => $paymentMethod->getDescription(),
                    'sort' => $paymentMethod->getSortOrder(),
                    'image' => $paymentMethod->getImage(),
                    'instructions' => static::normalizeDetails($paymentMethod->getAdditionalDetails()),
                    'qr_image' => method_exists($paymentMethod, 'getQrImage') ? $paymentMethod->getQrImage() : null,
                ];
            }
        }

        usort($paymentMethods, function ($a, $b) {
            if ($a['sort'] == $b['sort']) {
                return 0;
            }

            return ($a['sort'] < $b['sort']) ? -1 : 1;
        });

        return $paymentMethods;
    }

    /**
     * Returns payment redirect url if have any
     *
     * @param  Cart  $cart
     * @return string
     */
    public function getRedirectUrl($cart)
    {
        $payment = app(Config::get('payment_methods.'.$cart->payment->method.'.class'));

        return $payment->getRedirectUrl();
    }

    /**
     * Returns payment method additional information
     *
     * @param  string  $code
     * @return array
     */
    public static function getAdditionalDetails($code)
    {
        $paymentMethodClass = app(Config::get('payment_methods.'.$code.'.class'));

        return $paymentMethodClass->getAdditionalDetails();
    }

    /**
     * Returns the instructions a customer needs to pay with a payment method, as a title and value pair plus an optional QR image.
     *
     * @param  string|null  $code
     * @return array
     */
    public static function getInstructions($code)
    {
        $class = Config::get('payment_methods.'.$code.'.class');

        if (! $class) {
            return ['details' => [], 'qr_image' => null];
        }

        $paymentMethodClass = app($class);

        return [
            'details' => static::normalizeDetails($paymentMethodClass->getAdditionalDetails()),
            'qr_image' => method_exists($paymentMethodClass, 'getQrImage') ? $paymentMethodClass->getQrImage() : null,
        ];
    }

    /**
     * Keep only a well formed title and value pair so views never receive a malformed structure.
     *
     * @param  mixed  $details
     * @return array
     */
    protected static function normalizeDetails($details)
    {
        if (! is_array($details) || ! isset($details['title'], $details['value'])) {
            return [];
        }

        return ['title' => (string) $details['title'], 'value' => (string) $details['value']];
    }
}
