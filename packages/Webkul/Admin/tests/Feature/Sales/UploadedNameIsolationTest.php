<?php

use App\Repositories\MediaAssetRepository;
use App\Support\UploadedFileName;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Webkul\Customer\Models\Customer;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Payment\Repositories\PaymentReceiptRepository;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Sales\Models\Shipment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    config(['responsecache.enabled' => false]);

    Storage::fake('local');
    Mail::fake();
});

/**
 * Text nodes carrying the needle inside the page's Vue root, paired with whether a v-pre ancestor keeps Vue from compiling them.
 *
 * @return array<int, bool>
 */
function uploadedNameNodes(string $html, string $needle): array
{
    if (! preg_match('#<div[^>]*\bid="app"[^>]*>.*#s', $html, $app)) {
        return [];
    }

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8"?><body>'.preg_replace('#<script\b[^>]*>.*?</script>#s', '', $app[0]).'</body>');
    $xpath = new DOMXPath($document);
    $nodes = [];

    foreach ($xpath->query('//text()[contains(., "'.$needle.'")]') as $node) {
        $nodes[] = $xpath->query('ancestor::*[@v-pre]', $node)->length > 0;
    }

    return $nodes;
}

/**
 * Assert the needle is rendered inside the Vue root at least once and never where Vue would compile it.
 */
function expectUploadedNameIsolated(string $html, string $needle): void
{
    $nodes = uploadedNameNodes($html, $needle);

    expect($nodes)->not->toBeEmpty()
        ->and(array_filter($nodes, fn ($guarded) => ! $guarded))->toBeEmpty();
}

/**
 * Create an unpaid bank transfer order, for a guest unless a customer is given.
 */
function uploadedNameOrder(?Customer $customer = null, array $attributes = []): Order
{
    $buyer = $customer ?? Customer::factory()->create();
    $product = (new ProductFaker)->getSimpleProductFactory()->create();
    $order = Order::factory()->create($attributes + [
        'customer_id' => $customer?->id,
        'is_guest' => $customer ? 0 : 1,
        'customer_email' => $buyer->email,
        'customer_first_name' => $buyer->first_name,
        'customer_last_name' => $buyer->last_name,
        'grand_total' => 10,
        'base_grand_total' => 10,
        'grand_total_invoiced' => 0,
        'base_grand_total_invoiced' => 0,
        'grand_total_refunded' => 0,
        'base_grand_total_refunded' => 0,
        'sub_total' => 10,
        'base_sub_total' => 10,
        'sub_total_invoiced' => 0,
        'base_sub_total_invoiced' => 0,
        'base_currency_code' => core()->getBaseCurrencyCode(),
        'channel_currency_code' => core()->getCurrentCurrencyCode(),
        'order_currency_code' => core()->getCurrentCurrencyCode(),
    ]);

    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'banktransfer']);

    foreach ([OrderAddress::ADDRESS_TYPE_BILLING, OrderAddress::ADDRESS_TYPE_SHIPPING] as $type) {
        OrderAddress::factory()->create([
            'order_id' => $order->id,
            'address_type' => $type,
            'first_name' => $buyer->first_name,
            'last_name' => $buyer->last_name,
            'address' => 'Test address',
            'country' => 'US',
            'state' => 'CA',
            'city' => 'Test city',
            'postcode' => '10000',
            'phone' => '0123456789',
        ]);
    }

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'type' => 'simple',
        'price' => 10,
        'base_price' => 10,
        'total' => 10,
        'base_total' => 10,
    ]);

    return $order;
}

/**
 * Save a receipt whose name and review note were stored before file names were cleaned.
 */
function legacyReceipt(Order $order): void
{
    app(PaymentReceiptRepository::class)->create([
        'order_id' => $order->id,
        'receipt_path' => 'receipts/legacy.png',
        'original_name' => "Legacy receipt {{ \$emit.constructor('window.__probe=7*193')() }}.png",
        'status' => 'rejected',
        'admin_notes' => 'Legacy note {{ 7*197 }}',
    ]);

    $order->update(['workflow_status' => 'payment_clarification']);
}

it('gives a safe display name for any client file name', function (string $given, string $expected) {
    expect(UploadedFileName::clean($given))->toBe($expected);
})->with([
    'ordinary name' => ['Bank slip 12 Nov (2).png', 'Bank slip 12 Nov (2).png'],
    'template expression' => ["{{ \$emit.constructor('alert(1)')() }}.png", "\$emit.constructor('alert(1)')() .png"],
    'split delimiters' => ['{ { 7*191 } }.png', '7*191 .png'],
    'direction override and control characters' => ["slip\u{202E}gnp.exe\u{0000}\u{0007}.png", 'slipgnp.exe.png'],
    'line breaks and tabs' => ["slip\r\n\tone.png", 'slip one.png'],
    'non-latin name' => ['付款凭证 十一月.jpg', '付款凭证 十一月.jpg'],
    'over the length limit' => [str_repeat('a', 300).'.png', str_repeat('a', 255)],
]);

it('stores a receipt file name without template delimiters when a guest uploads through the signed link', function () {
    $order = uploadedNameOrder();
    $link = URL::temporarySignedRoute('payment.orders.receipt.show', now()->addDay(), ['id' => $order->id]);

    post($link, ['receipt' => UploadedFile::fake()->image("QA slip {{ \$emit.constructor('window.__probe=7*193')() }}.png")])->assertRedirect();

    $stored = app(PaymentReceiptRepository::class)->findOneWhere(['order_id' => $order->id])->original_name;

    expect($stored)->toStartWith('QA slip')
        ->toEndWith('.png')
        ->not->toContain('{')
        ->not->toContain('}');
});

it('stores a receipt file name without template delimiters when a signed-in customer uploads', function () {
    $customer = Customer::factory()->create();
    $order = uploadedNameOrder($customer);

    $this->actingAs($customer, 'customer');

    post(route('customer.orders.receipt.upload', $order->id), ['receipt' => UploadedFile::fake()->image('QA slip {{ 7*193 }}.png')])->assertRedirect();

    expect(app(PaymentReceiptRepository::class)->findOneWhere(['order_id' => $order->id])->original_name)->toBe('QA slip 7*193 .png');
});

it('stores a Media Bank file name without template delimiters', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $this->post(route('admin.media.assets.store'), ['file' => UploadedFile::fake()->image('QA media {{ 7*191 }}.png', 64, 64)], ['Accept' => 'application/json'])->assertCreated();

    expect(app(MediaAssetRepository::class)->all()->last()->original_name)->toBe('QA media 7*191 .png');
});

it('renders a stored receipt name and review note where Vue never compiles them on the admin order page', function (array $permissions) {
    $order = uploadedNameOrder();

    legacyReceipt($order);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => $permissions]);

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    $html = get(route('admin.sales.orders.view', $order->id))->assertOk()->getContent();

    expectUploadedNameIsolated($html, 'Legacy receipt');
    expectUploadedNameIsolated($html, 'Legacy note');
})->with([
    'administrator who can manage the workflow' => [['sales', 'sales.orders', 'sales.orders.view', 'sales.orders.workflow']],
    'read-only administrator' => [['sales', 'sales.orders', 'sales.orders.view']],
]);

it('keeps the order workflow panel collapsible and its forms intact', function () {
    $order = uploadedNameOrder();

    app(PaymentReceiptRepository::class)->create([
        'order_id' => $order->id,
        'receipt_path' => 'receipts/pending.png',
        'original_name' => 'Pending slip.png',
        'status' => 'pending',
    ]);

    $order->update(['workflow_status' => 'awaiting_verification']);

    $this->loginAsAdmin();

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8"?>'.get(route('admin.sales.orders.view', $order->id))->assertOk()->getContent());
    $xpath = new DOMXPath($document);
    $panel = '//div[contains(@class, "msp-workflow")]//div[@v-show="isOpen" and not(@v-pre)]/div[@v-pre]';

    expect($xpath->query($panel)->length)->toBe(1)
        ->and($xpath->query($panel.'//a[contains(@href, "/sales/receipts/") and normalize-space(text()) = "Pending slip.png"]')->length)->toBe(1)
        ->and($xpath->query($panel.'//form[contains(@action, "/approve")]//input[@name="_token"]')->length)->toBe(1)
        ->and($xpath->query($panel.'//form[contains(@action, "/reject")]//input[@name="notes"]')->length)->toBe(1);
});

it('renders a stored receipt name and review note where Vue never compiles them on the customer order page', function () {
    $customer = Customer::factory()->create();
    $order = uploadedNameOrder($customer);

    legacyReceipt($order);

    $this->actingAs($customer, 'customer');

    $html = get(route('shop.customers.account.orders.view', $order->id))->assertOk()->getContent();

    expectUploadedNameIsolated($html, 'Legacy receipt');
    expectUploadedNameIsolated($html, 'Legacy note');
});

it('renders stored receipt and shipment text where Vue never compiles it on the guest receipt page', function () {
    $order = uploadedNameOrder();

    legacyReceipt($order);

    Shipment::factory()->create(['order_id' => $order->id, 'carrier_title' => 'Carrier sentinel {{ 7*199 }}', 'track_number' => 'QA{{ 7*211 }}']);

    $html = get(URL::temporarySignedRoute('payment.orders.receipt.show', now()->addDay(), ['id' => $order->id]))->assertOk()->getContent();

    expectUploadedNameIsolated($html, 'Legacy receipt');
    expectUploadedNameIsolated($html, 'Legacy note');
    expectUploadedNameIsolated($html, 'Carrier sentinel');
});

it('renders a buyer name where Vue never compiles it in the dashboard order queue', function () {
    uploadedNameOrder(null, ['customer_first_name' => 'Queue sentinel {{ 7*191 }}', 'customer_last_name' => 'Buyer']);

    $this->loginAsAdmin();

    expectUploadedNameIsolated('<div id="app">'.view('manpleasure-admin::stitch-dashboard')->render().'</div>', 'Queue sentinel');
});
