<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Webkul\Customer\Models\Customer;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Sales\Models\Refund;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\RefundRepository;

/**
 * Create a Bank Transfer order of one line, confirm its payment and invoice it through the invoice repository, so
 * every refund total is checked against a real invoiced amount.
 */
function refundGuardOrder(float $price = 100): array
{
    $product = (new ProductFaker([]))->getSimpleProductFactory()->create();
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_email' => $customer->email,
        'customer_first_name' => $customer->first_name,
        'customer_last_name' => $customer->last_name,
        'status' => 'processing',
        'grand_total' => $price, 'base_grand_total' => $price,
        'grand_total_invoiced' => 0, 'base_grand_total_invoiced' => 0,
        'grand_total_refunded' => 0, 'base_grand_total_refunded' => 0,
        'sub_total' => $price, 'base_sub_total' => $price,
        'sub_total_invoiced' => 0, 'base_sub_total_invoiced' => 0,
        'sub_total_refunded' => 0, 'base_sub_total_refunded' => 0,
        'shipping_amount' => 0, 'base_shipping_amount' => 0,
        'shipping_invoiced' => 0, 'base_shipping_invoiced' => 0,
        'shipping_refunded' => 0, 'base_shipping_refunded' => 0,
    ]);
    $item = OrderItem::factory()->create([
        'product_id' => $product->id, 'order_id' => $order->id, 'sku' => $product->sku, 'type' => $product->type, 'name' => $product->name,
        'qty_ordered' => 1, 'qty_invoiced' => 0, 'qty_shipped' => 0, 'qty_refunded' => 0, 'qty_canceled' => 0,
        'price' => $price, 'base_price' => $price, 'total' => $price, 'base_total' => $price,
        'total_invoiced' => 0, 'base_total_invoiced' => 0, 'amount_refunded' => 0, 'base_amount_refunded' => 0,
        'tax_amount' => 0, 'base_tax_amount' => 0, 'discount_amount' => 0, 'base_discount_amount' => 0,
    ]);
    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'banktransfer', 'method_title' => 'Bank Transfer']);
    foreach ([OrderAddress::ADDRESS_TYPE_BILLING, OrderAddress::ADDRESS_TYPE_SHIPPING] as $type) {
        OrderAddress::factory()->create(['order_id' => $order->id, 'customer_id' => $customer->id, 'address_type' => $type]);
    }
    $order->forceFill(['workflow_status' => 'confirmed'])->save();
    app(InvoiceRepository::class)->create(['order_id' => $order->id, 'invoice' => ['items' => [$item->id => 1]]]);

    return [$order->fresh(), $item->fresh(), $product];
}

/**
 * Call the refund repository with a complete refund payload.
 */
function refundGuardCreate(Order $order, array $refund): mixed
{
    return app(RefundRepository::class)->create([
        'order_id' => $order->id,
        'refund' => $refund + ['items' => [], 'shipping' => 0, 'adjustment_refund' => 0, 'adjustment_fee' => 0],
    ]);
}

/**
 * The refund-related state of an order: refund rows, refunded totals, the line's refunded quantity and stock.
 */
function refundGuardState(Order $order, OrderItem $item, $product): array
{
    $order = $order->fresh();

    return [
        'refunds' => Refund::where('order_id', $order->id)->count(),
        'refunded' => round((float) $order->base_grand_total_refunded, 4),
        'qty_refunded' => (float) $item->fresh()->qty_refunded,
        'stock' => (float) $product->fresh()->inventories()->sum('qty'),
    ];
}

beforeEach(function () {
    Mail::fake();
});

it('refuses a refund whose total is zero or negative and leaves no trace', function (array $refund) {
    [$order, $item, $product] = refundGuardOrder();
    $refund['items'] = array_key_exists('items', $refund) ? [$item->id => $refund['items']] : [];
    $before = refundGuardState($order, $item, $product);
    Mail::fake();
    Event::fake(['sales.refund.save.after']);

    expect(fn () => refundGuardCreate($order, $refund))->toThrow(ValidationException::class);

    expect(refundGuardState($order, $item, $product))->toBe($before);
    Event::assertNotDispatched('sales.refund.save.after');
    Mail::assertNothingOutgoing();
})->with([
    'fee larger than the refunded line' => [['items' => 1, 'adjustment_fee' => 150]],
    'fee only, no line' => [['adjustment_fee' => 50]],
    'fee equal to the refunded line' => [['items' => 1, 'adjustment_fee' => 100]],
    'nothing at all' => [[]],
]);

it('refuses a zero or negative refund in the admin refund form and stores nothing', function (int $qty, float $fee) {
    [$order, $item, $product] = refundGuardOrder();
    $before = refundGuardState($order, $item, $product);
    Event::fake(['sales.refund.save.after']);
    $this->loginAsAdmin();

    $this->post(route('admin.sales.refunds.store', $order->id), ['refund' => [
        'items' => [$item->id => $qty], 'shipping' => 0, 'adjustment_refund' => 0, 'adjustment_fee' => $fee,
    ]])->assertRedirect()->assertSessionHas('error', trans('admin::app.sales.refunds.create.invalid-refund-amount-error'));

    expect(refundGuardState($order, $item, $product))->toBe($before);
    Event::assertNotDispatched('sales.refund.save.after');
})->with([
    'negative total' => [1, 150],
    'fee only' => [0, 50],
]);

it('no longer lets a negative refund reopen the refundable amount of a fully refunded order', function () {
    [$order, $item, $product] = refundGuardOrder();

    refundGuardCreate($order, ['items' => [$item->id => 1]]);

    expect(fn () => refundGuardCreate($order, ['adjustment_fee' => 50]))->toThrow(ValidationException::class);
    expect(fn () => refundGuardCreate($order, ['adjustment_refund' => 50]))->toThrow(ValidationException::class);

    expect(Refund::where('order_id', $order->id)->count())->toBe(1)
        ->and(round((float) Refund::where('order_id', $order->id)->sum('base_grand_total'), 4))->toBe(100.0)
        ->and(round((float) $order->fresh()->base_grand_total_refunded, 4))->toBe(100.0)
        ->and(app(RefundRepository::class)->getRefundableAmount($order->fresh()))->toBe(0.0);
});

it('refuses a refund above what is still refundable and leaves no trace', function () {
    [$order, $item, $product] = refundGuardOrder();
    $before = refundGuardState($order, $item, $product);
    Event::fake(['sales.refund.save.after']);

    expect(fn () => refundGuardCreate($order, ['adjustment_refund' => 100.01]))->toThrow(ValidationException::class);

    expect(refundGuardState($order, $item, $product))->toBe($before);
    Event::assertNotDispatched('sales.refund.save.after');
});

it('accepts positive refunds up to the invoiced amount, with or without a fee', function () {
    [$order, $item, $product] = refundGuardOrder();
    Event::fake(['sales.refund.save.after']);

    $first = refundGuardCreate($order, ['items' => [$item->id => 1], 'adjustment_fee' => 10]);
    $second = refundGuardCreate($order, ['adjustment_refund' => 10]);
    $state = refundGuardState($order, $item, $product);

    expect(round((float) $first->base_grand_total, 4))->toBe(90.0)
        ->and(round((float) $second->base_grand_total, 4))->toBe(10.0)
        ->and([$state['refunds'], $state['refunded'], $state['qty_refunded']])->toBe([2, 100.0, 1.0])
        ->and(fn () => refundGuardCreate($order, ['adjustment_refund' => 0.01]))->toThrow(ValidationException::class);
    Event::assertDispatchedTimes('sales.refund.save.after', 2);
});
