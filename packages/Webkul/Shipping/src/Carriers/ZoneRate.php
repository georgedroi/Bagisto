<?php

namespace Webkul\Shipping\Carriers;

use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Support\ZoneRateTable;

class ZoneRate extends AbstractShipping
{
    /**
     * Code of the shipping method.
     *
     * @var string
     */
    protected $code = 'zonerate';

    /**
     * Code of the shipping method.
     *
     * @var string
     */
    protected $method = 'zonerate_zonerate';

    /**
     * Calculate the delivery fee for the cart. Returns false when the destination is not served.
     *
     * @return CartShippingRate|false
     */
    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return $this->getRate();
    }

    /**
     * Get the delivery fee from the zone and the order subtotal.
     *
     * @return CartShippingRate|false
     */
    public function getRate()
    {
        $cart = Cart::getCart();

        $address = $cart?->shipping_address;

        if (! $address) {
            return false;
        }

        $quote = $this->getTable()->quote(
            $address->country,
            $address->postcode,
            $address->state,
            (float) $cart->base_sub_total
        );

        if (! $quote) {
            return false;
        }

        $cartShippingRate = new CartShippingRate;

        $cartShippingRate->carrier = $this->getCode();
        $cartShippingRate->carrier_title = $this->getConfigData('title');
        $cartShippingRate->method = $this->getMethod();
        $cartShippingRate->method_title = $this->getConfigData('title');
        $cartShippingRate->method_description = $this->getConfigData('description');
        $cartShippingRate->price = core()->convertPrice($quote['rate']);
        $cartShippingRate->base_price = $quote['rate'];

        return $cartShippingRate;
    }

    /**
     * Build the zone and rate table from the configuration.
     */
    public function getTable(): ZoneRateTable
    {
        return new ZoneRateTable(
            $this->getConfigData('zones'),
            $this->getConfigData('rates')
        );
    }
}
