<?php

use App\Support\OrderWorkflow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Customer\Models\Customer;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Payment\Repositories\PaymentReceiptRepository;
use Webkul\Sales\Repositories\InvoiceItemRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shop\Mail\Order\CreatedNotification;
use Webkul\Shop\Mail\Order\InvoicedNotification;
use Webkul\Shop\Mail\Order\ShippedNotification;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

it('checks out a manual payment order and records verified payment and physical shipment', function (string $method) {
    Mail::fake();
    Storage::fake('local');
    $channel = core()->getCurrentChannel();
    $channel->theme = 'manpleasure';
    $methods = array_fill_keys(array_keys(config('payment_methods')), ['active' => false]);
    $methods[$method] = [
        'active' => true,
        'account_number' => 'QA-ONLY',
        'usdt_erc20_address' => '0x'.str_repeat('1', 40),
        'instructions' => 'QA payment instructions',
    ];
    app(CoreConfigRepository::class)->create([
        'channel' => core()->getCurrentChannelCode(),
        'locale' => 'en',
        'sales' => [
            'payment_methods' => $methods,
            'carriers' => [
                'free' => ['active' => false],
                'flatrate' => ['active' => false],
                'zonerate' => ['active' => true, 'zones' => 'west: MY', 'rates' => 'west, 0, *, 15'],
            ],
        ],
        'emails' => ['general' => ['notifications' => ['emails' => ['general' => ['notifications' => ['new_order' => true, 'new_invoice' => true, 'new_shipment' => true]]]]]],
    ]);

    $customer = Customer::factory()->create();
    $product = (new ProductFaker(['attribute_value' => [
        'price' => ['float_value' => 100],
        'special_price' => ['float_value' => null],
        'manage_stock' => ['boolean_value' => true],
    ]]))->getSimpleProductFactory()->create();
    $source = $product->inventories->first()->inventory_source_id;
    $stock = (int) $product->inventory_source_qty($source);
    $this->actingAs($customer, 'customer');

    postJson(route('shop.api.checkout.cart.store'), ['product_id' => $product->id, 'quantity' => 1])->assertOk();
    postJson(route('shop.checkout.onepage.addresses.store'), ['billing' => [
        'first_name' => $customer->first_name,
        'last_name' => $customer->last_name,
        'email' => $customer->email,
        'address' => ['QA address'],
        'city' => 'Kuala Lumpur',
        'country' => 'MY',
        'state' => 'Kuala Lumpur',
        'postcode' => '50150',
        'phone' => '0123456789',
        'use_for_shipping' => true,
    ]])->assertOk()->assertJsonPath('data.shippingMethods.zonerate.rates.0.base_price', 15);

    $available = postJson(route('shop.checkout.onepage.shipping_methods.store'), ['shipping_method' => 'zonerate_zonerate'])->assertOk()->json('payment_methods');
    expect(array_column($available, 'method'))->toBe([$method]);
    postJson(route('shop.checkout.onepage.payment_methods.store'), ['payment' => ['method' => $method]])->assertOk();
    postJson(route('shop.checkout.onepage.orders.store'))->assertOk()->assertJsonPath('data.redirect_url', route('shop.checkout.onepage.success'));

    $order = app(OrderRepository::class)->with(['items', 'payment'])->findOrFail(session('order_id'));
    $item = $order->items->first();
    expect($order->payment->method)->toBe($method)
        ->and((float) $order->base_grand_total)->toBe(115.0)
        ->and($order->invoices)->toHaveCount(0)
        ->and((int) $product->ordered_inventories()->sum('qty'))->toBe(1)
        ->and((int) $product->fresh()->inventory_source_qty($source))->toBe($stock);
    $success = get(route('shop.checkout.onepage.success'))->assertOk()->assertSeeText('QA payment instructions');
    $order->checkout_message = '<img src=x onerror="alert(1)">';
    expect(view('shop::checkout.success', compact('order'))->render())->toContain('&lt;img src=x onerror=')->not->toContain('<img src=x onerror=');
    Mail::assertQueued(CreatedNotification::class, function ($mail) use ($product) {
        $html = $mail->render();

        return str_contains($html, trans('shop::discreet.item', ['number' => 1]))
            && ! str_contains($html, e($product->name))
            && ! str_contains($html, e($product->sku));
    });

    if ($method === 'duitnowqr_manual') {
        $document = new DOMDocument;
        @$document->loadHTML($success->getContent());
        expect((new DOMXPath($document))->query('//a[contains(@href, "/payment/orders/'.$order->id.'/receipt")]')->length)->toBe(1);
        post(route('customer.orders.receipt.upload', $order->id), ['receipt' => UploadedFile::fake()->image('proof.png')])->assertRedirect();
        $receipt = app(PaymentReceiptRepository::class)->findOneWhere(['order_id' => $order->id]);
        $this->loginAsAdmin();
        post(route('admin.sales.receipts.approve', $receipt->id))->assertRedirect();
        post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'confirmed', 'workflow_status' => 'packing'])->assertRedirect();
        post(route('admin.sales.orders.awb.upload', $order->id), ['awb' => UploadedFile::fake()->image('awb.png')])->assertRedirect();
        post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'packing', 'workflow_status' => 'ready_to_ship'])->assertRedirect();
    } else {
        $this->loginAsAdmin();
        post(route('admin.sales.invoices.store', $order->id), ['invoice' => ['items' => [$item->id => 1]]])->assertRedirect();
    }

    expect($order->fresh()->invoices)->toHaveCount(1);
    Mail::assertQueued(InvoicedNotification::class);
    $invoice = $order->fresh()->invoices->first();
    app(InvoiceItemRepository::class)->update(['additional' => ['attributes' => [['attribute_name' => 'QA option', 'option_label' => 'QA private option']]]], $invoice->items->first()->id);
    $invoice = $invoice->fresh();
    $invoiceMail = new class($invoice) extends InvoicedNotification
    {
        /**
         * HTML handed to the PDF renderer by the actual email attachment path.
         */
        public string $attachmentHtml = '';

        /**
         * Capture rendering input while keeping the integration test independent of the PDF engine.
         */
        public function generatePdf(string $html): string
        {
            $this->attachmentHtml = $html;

            return '%PDF-1.4';
        }
    };
    expect($invoiceMail->attachments())->toHaveCount(1);
    expect($invoiceMail->attachmentHtml)->toContain(trans('shop::discreet.item', ['number' => 1]))
        ->not->toContain(e($product->name))
        ->not->toContain(e($product->sku))
        ->not->toContain('QA private option');
    expect(view('shop::customers.account.orders.pdf', ['invoice' => $invoice, 'orderCurrencyCode' => $order->order_currency_code])->render())
        ->toContain(e($product->name))
        ->toContain(e($product->sku))
        ->toContain('QA private option');
    post(route('admin.sales.shipments.store', $order->id), ['shipment' => [
        'source' => $source,
        'items' => [$item->id => [$source => 1]],
        'carrier_title' => 'QA Courier',
        'track_number' => 'QA-TRANSACTION',
    ]])->assertRedirect(route('admin.sales.orders.view', $order->id));
    expect($order->fresh()->shipments)->toHaveCount(1)
        ->and((int) $product->fresh()->inventory_source_qty($source))->toBe($stock - 1)
        ->and((int) $product->ordered_inventories()->sum('qty'))->toBe(0);
    Mail::assertQueued(ShippedNotification::class, function ($mail) use ($product) {
        $html = $mail->render();

        return str_contains($html, 'QA-TRANSACTION')
            && ! str_contains($html, e($product->name))
            && ! str_contains($html, e($product->sku));
    });

    if ($method === 'duitnowqr_manual') {
        foreach (['ready_to_ship' => 'handed_to_courier', 'handed_to_courier' => 'in_transit', 'in_transit' => 'out_for_delivery', 'out_for_delivery' => 'delivered'] as $from => $to) {
            post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => $from, 'workflow_status' => $to])->assertRedirect();
        }

        expect(OrderWorkflow::current($order->fresh()))->toBe('delivered');
    }
})->with(['banktransfer', 'duitnowqr_manual', 'cryptotransfer']);
