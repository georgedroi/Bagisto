<?php

use Illuminate\Database\Eloquent\Collection;
use Webkul\Admin\Mail\Order\InventorySourceNotification;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Sales\Models\Shipment;
use Webkul\Sales\Models\ShipmentItem;

/**
 * Configure a manual payment method with receiving bank details and build an unsaved shipment of one order line.
 */
function inventorySourceEmailShipment(string $method, string $workflowStatus): Shipment
{
    app(CoreConfigRepository::class)->create([
        'channel' => core()->getCurrentChannelCode(),
        'locale' => 'en',
        'sales' => ['payment_methods' => [$method => [
            'active' => true,
            'title' => 'Synthetic manual payment',
            'instructions' => 'Synthetic receiving instructions',
            'bank_name' => 'Synthetic receiving bank',
            'account_name' => 'Synthetic account holder',
            'account_number' => 'QA-ONLY-ACCOUNT-NUMBER',
        ]]],
    ]);
    $order = new Order(['id' => 99999, 'increment_id' => 'SYNTHETIC-ORDER', 'created_at' => now(), 'shipping_title' => 'Synthetic shipping']);
    $order->workflow_status = $workflowStatus;
    $order->setRelation('payment', new OrderPayment(['method' => $method]));
    $address = new OrderAddress(['first_name' => 'QA', 'last_name' => 'Recipient', 'address' => 'Synthetic address', 'city' => 'QA City', 'state' => 'QA State', 'postcode' => '00000', 'phone' => '0000000000']);
    $shippingAddress = clone $address;
    $shippingAddress->address_type = OrderAddress::ADDRESS_TYPE_SHIPPING;
    $billingAddress = clone $address;
    $billingAddress->address_type = OrderAddress::ADDRESS_TYPE_BILLING;
    $order->setRelation('addresses', new Collection([$shippingAddress, $billingAddress]));
    $shipment = new Shipment(['order_id' => 99999, 'increment_id' => 'SYNTHETIC-SHIPMENT', 'carrier_title' => 'Synthetic carrier', 'track_number' => 'SYNTHETIC-TRACKING']);
    $shipment->setRelation('order', $order);
    $shipment->setRelation('inventory_source', new InventorySource(['contact_name' => 'QA Warehouse', 'contact_email' => 'warehouse@example.test']));
    $shipment->setRelation('items', new Collection([new ShipmentItem([
        'name' => 'Warehouse product name',
        'sku' => 'WAREHOUSE-PICK-SKU',
        'qty' => 1,
        'base_price' => 10,
        'base_price_incl_tax' => 10,
        'additional' => ['attributes' => [['attribute_name' => 'Size', 'option_label' => 'Warehouse option label']]],
    ])]));

    return $shipment;
}

it('gives the warehouse the real SKU, name and options with the carrier and tracking number', function (string $method) {
    $html = (new InventorySourceNotification(inventorySourceEmailShipment($method, 'confirmed')))->render();

    expect(trans('admin::app.emails.orders.sku'))->not->toBe('admin::app.emails.orders.sku');
    expect($html)->toContain(trans('admin::app.emails.orders.sku'))
        ->toContain('WAREHOUSE-PICK-SKU')
        ->toContain('Warehouse product name')
        ->toContain('Warehouse option label')
        ->toContain('Synthetic carrier')
        ->toContain('SYNTHETIC-TRACKING')
        ->not->toContain(trans('shop::discreet.item', ['number' => 1]));
})->with(['banktransfer', 'duitnowqr_manual', 'cryptotransfer']);

it('leaves the receiving bank account and payment instructions out of the warehouse email', function (string $method) {
    $html = (new InventorySourceNotification(inventorySourceEmailShipment($method, 'confirmed')))->render();

    expect($html)->toContain('Synthetic manual payment')
        ->not->toContain('QA-ONLY-ACCOUNT-NUMBER')
        ->not->toContain('Synthetic receiving bank')
        ->not->toContain('Synthetic account holder')
        ->not->toContain('Synthetic receiving instructions');
})->with(['banktransfer', 'duitnowqr_manual', 'cryptotransfer']);

it('shows the payment verification status of a QR Code or Bank Transfer order', function (string $method) {
    $verified = (new InventorySourceNotification(inventorySourceEmailShipment($method, 'confirmed')))->render();
    $pending = (new InventorySourceNotification(inventorySourceEmailShipment($method, 'awaiting_verification')))->render();

    expect($verified)->toContain(trans('admin::order-status.receipts').' : '.trans('admin::order-status.receipt-approved'));
    expect($pending)->toContain(trans('admin::order-status.receipts').' : '.trans('admin::order-status.receipt-pending'));
})->with(['banktransfer', 'duitnowqr_manual']);
