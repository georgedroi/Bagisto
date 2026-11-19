<?php

use App\Repositories\OrderQueueRepository;
use App\Support\OrderWorkflow;
use App\Support\WhatsAppMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Customer\Models\Customer;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Payment\Repositories\PaymentReceiptRepository;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Sales\Models\Shipment;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Storage::fake('local');
    Mail::fake();
});

/**
 * Create an unpaid physical DuitNow order with invoiceable items.
 */
function createDuitNowWorkflowOrder(): Order
{
    $customer = Customer::factory()->create();
    $product = (new ProductFaker)->getSimpleProductFactory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_email' => $customer->email,
        'customer_first_name' => $customer->first_name,
        'customer_last_name' => $customer->last_name,
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
    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'duitnowqr_manual']);

    foreach ([OrderAddress::ADDRESS_TYPE_BILLING, OrderAddress::ADDRESS_TYPE_SHIPPING] as $type) {
        OrderAddress::factory()->create([
            'order_id' => $order->id,
            'address_type' => $type,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
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

it('verifies a receipt, invoices once, prints an AWB and progresses through delivery', function (string $method) {
    $order = createDuitNowWorkflowOrder();
    $order->payment->update(['method' => $method]);
    $this->actingAs($order->customer, 'customer');

    post(route('customer.orders.receipt.upload', $order->id), [
        'receipt' => UploadedFile::fake()->image('proof.png'),
    ])->assertRedirect();

    $receipt = app(PaymentReceiptRepository::class)->findOneWhere(['order_id' => $order->id]);
    expect($order->fresh()->workflow_status)->toBe('awaiting_verification');

    $this->loginAsAdmin();
    post(route('admin.sales.receipts.approve', $receipt->id))->assertRedirect();
    expect($order->fresh()->workflow_status)->toBe('confirmed')
        ->and($order->invoices()->count())->toBe(1)
        ->and($order->items()->first()->qty_invoiced)->toEqual(1);
    post(route('admin.sales.receipts.approve', $receipt->id))->assertUnprocessable();
    expect($order->invoices()->count())->toBe(1);
    expect(fn () => app(InvoiceRepository::class)->create(['order_id' => $order->id, 'invoice' => ['items' => [$order->items->first()->id => 1]]]))->toThrow(ValidationException::class);
    expect($order->invoices()->count())->toBe(1);

    post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'confirmed', 'workflow_status' => 'packing', 'note' => 'Items checked'])->assertRedirect();
    post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'packing', 'workflow_status' => 'ready_to_ship'])->assertUnprocessable();
    post(route('admin.sales.orders.awb.upload', $order->id), ['awb' => UploadedFile::fake()->image('courier.png')])->assertRedirect();
    post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'packing', 'workflow_status' => 'ready_to_ship'])->assertRedirect();
    post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'ready_to_ship', 'workflow_status' => 'handed_to_courier'])->assertUnprocessable();

    Shipment::factory()->create(['order_id' => $order->id, 'carrier_title' => 'Test Courier', 'track_number' => 'QA123']);
    $order->items()->update(['qty_shipped' => 1]);

    foreach (['ready_to_ship' => 'handed_to_courier', 'handed_to_courier' => 'in_transit', 'in_transit' => 'out_for_delivery', 'out_for_delivery' => 'delivered'] as $from => $to) {
        post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => $from, 'workflow_status' => $to])->assertRedirect();
    }

    expect($order->fresh()->workflow_status)->toBe('delivered')
        ->and($order->comments()->count())->toBe(6);
    get(route('admin.sales.orders.awb.print', $order->id))->assertOk()->assertSee(route('admin.sales.orders.awb.image', $order->id), false);
    get(route('admin.sales.orders.awb.image', $order->id))->assertOk();
    get(route('admin.sales.orders.view', $order->id))->assertOk()->assertSeeText(trans('admin::order-status.delivered'));
    getJson(route('admin.sales.orders.index', ['workflow' => 'delivered']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(1, 'records');
})->with(['duitnowqr_manual', 'banktransfer']);

it('requires signed guest receipt links and supports clarification and replacement receipts', function (string $method) {
    $order = createDuitNowWorkflowOrder();
    $order->payment->update(['method' => $method]);
    $order->update(['is_guest' => 1, 'customer_id' => null]);
    $link = URL::temporarySignedRoute('payment.orders.receipt.show', now()->addDay(), ['id' => $order->id]);

    get(route('payment.orders.receipt.show', $order->id))->assertForbidden();
    get(URL::temporarySignedRoute('payment.orders.receipt.show', now()->subMinute(), ['id' => $order->id]))->assertForbidden();
    $this->withSession(['order_id' => $order->id])->get(route('shop.checkout.onepage.success'))->assertOk()->assertSeeText(trans('payment::app.upload-receipt'));
    get($link)->assertOk();
    post($link, ['receipt' => UploadedFile::fake()->image('first.png')])->assertRedirect();
    $first = app(PaymentReceiptRepository::class)->findOneWhere(['order_id' => $order->id]);

    $this->loginAsAdmin();
    post(route('admin.sales.receipts.reject', $first->id), ['notes' => 'Reference does not match'])->assertRedirect();
    expect($order->fresh()->workflow_status)->toBe('payment_clarification');
    get($link)->assertOk()->assertSeeText('Reference does not match');
    post($link, ['receipt' => UploadedFile::fake()->image('replacement.png')])->assertRedirect();
    post(route('admin.sales.receipts.approve', $first->id))->assertUnprocessable();
    expect($order->fresh()->workflow_status)->toBe('awaiting_verification');

    $other = createDuitNowWorkflowOrder();
    get(str_replace('/'.$order->id.'/receipt', '/'.$other->id.'/receipt', $link))->assertForbidden();
})->with(['duitnowqr_manual', 'banktransfer']);

it('refuses payment bypass, skipped steps, stale changes and terminal order updates', function (string $method) {
    $order = createDuitNowWorkflowOrder();
    $order->payment->update(['method' => $method]);

    expect(fn () => Event::dispatch('sales.shipment.save.before', ['order_id' => $order->id]))->toThrow(ValidationException::class);

    $this->loginAsAdmin();
    post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'awaiting_payment', 'workflow_status' => 'packing'])->assertUnprocessable();
    $order->update(['workflow_status' => 'confirmed']);
    post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'confirmed', 'workflow_status' => 'delivered'])->assertUnprocessable();
    post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'packing', 'workflow_status' => 'ready_to_ship'])->assertConflict();
    $order->update(['status' => Order::STATUS_CANCELED]);
    post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'cancelled', 'workflow_status' => 'packing'])->assertUnprocessable();
    expect($order->fresh()->workflow_status)->toBe('confirmed');
})->with(['duitnowqr_manual', 'banktransfer']);

it('refuses unsafe uploads and prevents another customer from uploading proof', function (string $method) {
    $order = createDuitNowWorkflowOrder();
    $order->payment->update(['method' => $method]);
    $link = URL::temporarySignedRoute('payment.orders.receipt.show', now()->addDay(), ['id' => $order->id]);
    postJson($link, ['receipt' => UploadedFile::fake()->create('proof.php', 1, 'image/png')])->assertUnprocessable();
    postJson($link, ['receipt' => UploadedFile::fake()->image('large.png')->size(6000)])->assertUnprocessable();
    $this->actingAs(Customer::factory()->create(), 'customer');
    post(route('customer.orders.receipt.upload', $order->id), ['receipt' => UploadedFile::fake()->image('proof.png')])->assertRedirect();
    expect(app(PaymentReceiptRepository::class)->findWhere(['order_id' => $order->id]))->toHaveCount(0);
})->with(['duitnowqr_manual', 'banktransfer']);

it('denies a read-only administrator payment and workflow mutations', function () {
    $order = createDuitNowWorkflowOrder();
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['sales', 'sales.orders', 'sales.orders.view']]);
    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'awaiting_payment', 'workflow_status' => 'packing'])->assertClientError();
    post(route('admin.sales.receipts.approve', 1))->assertClientError();
    get(route('admin.sales.orders.awb.print', $order->id))->assertClientError();
    expect($order->fresh()->workflow_status)->toBeNull();
});

it('requires a reason for failed delivery and permits a recorded delivery retry', function () {
    $order = createDuitNowWorkflowOrder();
    $order->update(['status' => Order::STATUS_PROCESSING, 'workflow_status' => 'in_transit']);
    $this->loginAsAdmin();

    postJson(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'in_transit', 'workflow_status' => 'failed_to_deliver'])->assertUnprocessable();
    post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'in_transit', 'workflow_status' => 'failed_to_deliver', 'note' => 'Courier could not reach recipient'])->assertRedirect();
    post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'failed_to_deliver', 'workflow_status' => 'out_for_delivery'])->assertRedirect();

    expect($order->fresh()->workflow_status)->toBe('out_for_delivery')
        ->and($order->comments()->count())->toBe(2);
});

it('treats a fully invoiced order saved before the workflow as confirmed and lets it ship', function () {
    $order = createDuitNowWorkflowOrder();
    $order->update(['status' => Order::STATUS_PROCESSING, 'grand_total_invoiced' => 10, 'base_grand_total_invoiced' => 10]);
    $order->items()->update(['qty_invoiced' => 1]);

    expect($order->fresh()->workflow_status)->toBeNull()
        ->and(OrderWorkflow::current($order->fresh()))->toBe('confirmed');
    Event::dispatch('sales.shipment.save.before', ['order_id' => $order->id]);

    $this->loginAsAdmin();
    getJson(route('admin.sales.orders.index', ['workflow' => 'awaiting_payment']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(0, 'records');
    expect(getJson(route('admin.sales.orders.index'), ['X-Requested-With' => 'XMLHttpRequest'])->json('records.0.status'))->toContain(trans('admin::order-status.order-confirmed'));
    post(route('admin.sales.orders.workflow.update', $order->id), ['expected_status' => 'confirmed', 'workflow_status' => 'packing'])->assertRedirect();
    expect($order->fresh()->workflow_status)->toBe('packing');
});

it('treats a shipped order saved before the workflow as handed to the courier', function () {
    $order = createDuitNowWorkflowOrder();
    $order->update(['status' => Order::STATUS_COMPLETED, 'grand_total_invoiced' => 10, 'base_grand_total_invoiced' => 10]);
    Shipment::factory()->create(['order_id' => $order->id, 'carrier_title' => 'Test Courier', 'track_number' => 'QA123']);

    expect(OrderWorkflow::current($order->fresh()))->toBe('handed_to_courier');
});

it('lets an administrator review a pending receipt saved before the workflow', function () {
    $order = createDuitNowWorkflowOrder();
    $receipt = app(PaymentReceiptRepository::class)->create(['order_id' => $order->id, 'receipt_path' => 'receipts/old.png', 'original_name' => 'old.png', 'status' => 'pending']);

    expect(OrderWorkflow::current($order->fresh()))->toBe('awaiting_verification');

    $this->loginAsAdmin();
    post(route('admin.sales.receipts.approve', $receipt->id))->assertRedirect();
    expect($order->fresh()->workflow_status)->toBe('confirmed')
        ->and($order->invoices()->count())->toBe(1);
});

it('keeps legacy receipt stages consistent across order details, filters and dashboard tasks', function (string $receiptStatus, string $stage, string $label, string $method) {
    $order = createDuitNowWorkflowOrder();
    $order->payment->update(['method' => $method]);
    app(PaymentReceiptRepository::class)->create(['order_id' => $order->id, 'receipt_path' => 'receipts/legacy.png', 'original_name' => 'legacy.png', 'status' => $receiptStatus]);
    $this->loginAsAdmin();

    expect(OrderWorkflow::current($order->fresh()))->toBe($stage);

    $records = collect(getJson(route('admin.sales.orders.index', ['workflow' => $stage]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('records'));
    expect($records->pluck('id')->all())->toContain($order->id)
        ->and(strip_tags($records->firstWhere('id', $order->id)['status']))->toBe(trans('admin::order-status.'.$label));

    getJson(route('admin.sales.orders.index', ['workflow' => 'awaiting_payment']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(0, 'records');
    getJson(route('admin.sales.orders.index', ['queue' => 'awaiting_payment']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(0, 'records');
    expect(app(OrderQueueRepository::class)->workflowCounts()[$stage])->toBe(1);
    get(route('admin.seller_workspace.orders', ['workflow' => $stage]))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->firstWhere('id', $order->id)?->workspace_status === $stage);
})->with([
    'pending receipt' => ['pending', 'awaiting_verification', 'awaiting-verification'],
    'rejected receipt' => ['rejected', 'payment_clarification', 'payment-clarification'],
    'approved receipt' => ['approved', 'confirmed', 'order-confirmed'],
])->with(['duitnowqr_manual', 'banktransfer']);

it('counts a fully paid legacy order in fulfillment even when its last receipt was pending', function () {
    $order = createDuitNowWorkflowOrder();
    $order->update(['status' => Order::STATUS_PROCESSING, 'grand_total_invoiced' => 10, 'base_grand_total_invoiced' => 10]);
    app(PaymentReceiptRepository::class)->create(['order_id' => $order->id, 'receipt_path' => 'receipts/legacy.png', 'status' => 'pending']);
    $this->loginAsAdmin();

    expect(app(OrderQueueRepository::class)->workflowCounts()['confirmed'])->toBe(1)
        ->and(app(OrderQueueRepository::class)->workflowCounts()['awaiting_verification'])->toBe(0);
    getJson(route('admin.sales.orders.index', ['workflow' => 'confirmed']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(1, 'records');
    getJson(route('admin.sales.orders.index', ['queue' => 'to_ship']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(1, 'records');
});

it('uses the latest legacy receipt and preserves a saved or terminal workflow stage in every list', function () {
    $order = createDuitNowWorkflowOrder();
    $receipts = app(PaymentReceiptRepository::class);
    $receipts->create(['order_id' => $order->id, 'receipt_path' => 'receipts/old.png', 'status' => 'pending']);
    $receipts->create(['order_id' => $order->id, 'receipt_path' => 'receipts/latest.png', 'status' => 'rejected']);
    $this->loginAsAdmin();

    getJson(route('admin.sales.orders.index', ['workflow' => 'awaiting_verification']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(0, 'records');
    getJson(route('admin.sales.orders.index', ['workflow' => 'payment_clarification']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(1, 'records');

    $order->update(['workflow_status' => 'packing']);
    getJson(route('admin.sales.orders.index', ['workflow' => 'payment_clarification']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(0, 'records');
    getJson(route('admin.sales.orders.index', ['workflow' => 'packing']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(1, 'records');

    $order->update(['status' => Order::STATUS_CANCELED]);
    getJson(route('admin.sales.orders.index', ['workflow' => 'packing']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonCount(0, 'records');
    expect(app(OrderQueueRepository::class)->workflowCounts()['packing'])->toBe(0);
});

it('asks for a new receipt when the last receipt saved before the workflow was rejected', function () {
    $order = createDuitNowWorkflowOrder();
    app(PaymentReceiptRepository::class)->create(['order_id' => $order->id, 'receipt_path' => 'receipts/old.png', 'original_name' => 'old.png', 'status' => 'rejected']);
    $this->actingAs($order->customer, 'customer');

    expect(OrderWorkflow::current($order->fresh()))->toBe('payment_clarification');
    post(route('customer.orders.receipt.upload', $order->id), ['receipt' => UploadedFile::fake()->image('proof.png')])->assertRedirect();
    expect($order->fresh()->workflow_status)->toBe('awaiting_verification');
});

it('keeps an unpaid order saved before the workflow awaiting payment and refuses its shipment', function () {
    $order = createDuitNowWorkflowOrder();

    expect(OrderWorkflow::current($order))->toBe('awaiting_payment')
        ->and(fn () => Event::dispatch('sales.shipment.save.before', ['order_id' => $order->id]))->toThrow(ValidationException::class);
});

it('records a WhatsApp receipt without confirming payment and keeps a private audit trail', function (string $method) {
    $order = createDuitNowWorkflowOrder();
    $order->payment->update(['method' => $method]);
    $this->loginAsAdmin();
    get(route('admin.sales.orders.view', $order->id))->assertOk()->assertSee(route('admin.sales.orders.receipt.whatsapp', $order->id), false)->assertDontSee('we%20have%20received%20your%20payment', false);
    post(route('admin.sales.orders.receipt.whatsapp', $order->id), [
        'expected_status' => 'awaiting_payment',
        'receipt' => UploadedFile::fake()->image('whatsapp-proof.png'),
    ])->assertRedirect(route('admin.sales.orders.view', $order->id));

    $receipt = app(PaymentReceiptRepository::class)->findOneWhere(['order_id' => $order->id]);
    expect($receipt->status)->toBe('pending')
        ->and($order->fresh()->workflow_status)->toBe('awaiting_verification')
        ->and($order->fresh()->invoices)->toHaveCount(0)
        ->and($order->fresh()->comments)->toHaveCount(1)
        ->and((bool) $order->fresh()->comments->first()->customer_notified)->toBeFalse();
    Storage::disk('local')->assertExists($receipt->receipt_path);
    post(route('admin.sales.orders.receipt.whatsapp', $order->id), ['expected_status' => 'awaiting_payment', 'receipt' => UploadedFile::fake()->image('duplicate.png')])->assertConflict();
    expect(app(PaymentReceiptRepository::class)->findWhere(['order_id' => $order->id]))->toHaveCount(1);
    post(route('admin.sales.receipts.approve', $receipt->id))->assertRedirect();
    expect($order->fresh()->invoices)->toHaveCount(1)
        ->and(WhatsAppMessage::linkFor($order->fresh(), WhatsAppMessage::PAID))->not->toBeNull();
})->with(['duitnowqr_manual', 'banktransfer']);

it('refuses unauthorized unsafe and terminal WhatsApp receipt registration', function (string $method) {
    $order = createDuitNowWorkflowOrder();
    $order->payment->update(['method' => $method]);
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['sales.orders.view']]);
    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));
    post(route('admin.sales.orders.receipt.whatsapp', $order->id), ['expected_status' => 'awaiting_payment', 'receipt' => UploadedFile::fake()->image('proof.png')])->assertUnauthorized();
    $this->loginAsAdmin();
    post(route('admin.sales.orders.receipt.whatsapp', $order->id), ['expected_status' => 'awaiting_payment', 'receipt' => UploadedFile::fake()->createWithContent('proof.png', '<?php echo 1;')], ['Accept' => 'application/json'])->assertUnprocessable();
    $order->update(['status' => Order::STATUS_CANCELED]);
    post(route('admin.sales.orders.receipt.whatsapp', $order->id), ['expected_status' => 'awaiting_payment', 'receipt' => UploadedFile::fake()->image('proof.png')])->assertConflict();
    expect(app(PaymentReceiptRepository::class)->findWhere(['order_id' => $order->id]))->toHaveCount(0);
})->with(['duitnowqr_manual', 'banktransfer']);

it('refuses receipt workflow access for unsupported payment methods', function (string $method) {
    $order = createDuitNowWorkflowOrder();
    $order->payment->update(['method' => $method]);
    $link = URL::temporarySignedRoute('payment.orders.receipt.show', now()->addDay(), ['id' => $order->id]);
    get($link)->assertNotFound();
    postJson($link, ['receipt' => UploadedFile::fake()->image('proof.png')])->assertUnprocessable();
    expect(app(PaymentReceiptRepository::class)->findWhere(['order_id' => $order->id]))->toHaveCount(0);
})->with(['cryptotransfer', 'stripe']);

it('refuses direct invoicing before receipt verification', function (string $method) {
    $order = createDuitNowWorkflowOrder();
    $order->payment->update(['method' => $method]);
    $this->loginAsAdmin();
    $payload = ['invoice' => ['items' => [$order->items->first()->id => 1]]];
    postJson(route('admin.sales.invoices.store', $order->id), $payload)->assertUnprocessable();
    expect($order->fresh()->invoices)->toHaveCount(0);
    get(route('admin.sales.orders.view', $order->id))->assertOk()->assertDontSee('<v-create-invoices>', false)->assertDontSee('<v-create-shipment>', false);
    post(route('admin.sales.orders.receipt.whatsapp', $order->id), ['expected_status' => 'awaiting_payment', 'receipt' => UploadedFile::fake()->image('proof.png')])->assertRedirect();
    postJson(route('admin.sales.invoices.store', $order->id), $payload)->assertUnprocessable();
    expect($order->fresh()->invoices)->toHaveCount(0);
})->with(['duitnowqr_manual', 'banktransfer']);

it('rolls back receipt approval when invoice creation fails', function (string $method) {
    $order = createDuitNowWorkflowOrder();
    $order->payment->update(['method' => $method]);
    $receipt = app(PaymentReceiptRepository::class)->uploadReceipt($order->id, UploadedFile::fake()->image('proof.png'));
    Event::listen('sales.invoice.save.before', fn () => throw new RuntimeException('QA invoice creation failure'));
    expect(fn () => app(PaymentReceiptRepository::class)->approveReceipt($receipt->id))->toThrow(RuntimeException::class, 'QA invoice creation failure');
    expect($receipt->fresh()->status)->toBe('pending')
        ->and($order->fresh()->workflow_status)->toBe('awaiting_verification')
        ->and($order->fresh()->invoices)->toHaveCount(0);
})->with(['duitnowqr_manual', 'banktransfer']);

it('withholds paid WhatsApp messages until verification and renders an order-specific proof QR', function () {
    $order = createDuitNowWorkflowOrder();
    core()->getCurrentChannel()->theme = 'manpleasure';
    app(CoreConfigRepository::class)->create(['channel' => core()->getCurrentChannelCode(), 'locale' => 'en', 'sales' => ['payment_methods' => ['duitnowqr_manual' => ['instructions' => 'QA payment details']]]]);
    expect(WhatsAppMessage::linkFor($order, WhatsAppMessage::PAID))->toBeNull();
    $link = WhatsAppMessage::proofLinkFor($order);
    expect($link)->toStartWith('https://wa.me/')
        ->and(urldecode($link))->toContain('#'.$order->increment_id)
        ->and(urldecode($link))->not->toContain($order->items->first()->name);
    $qr = WhatsAppMessage::proofQrFor($order);
    expect($qr)->toStartWith('data:image/svg+xml;base64,')
        ->and(base64_decode(substr($qr, strpos($qr, ',') + 1)))->toContain('<svg');
    $this->withSession(['order_id' => $order->id])->get(route('shop.checkout.onepage.success'))->assertOk()->assertSee('data-whatsapp-proof-qr', false);
});

it('refuses payment confirmation links for partial or terminal manual orders', function (string $method) {
    $order = createDuitNowWorkflowOrder();
    $order->payment->update(['method' => $method]);
    expect(WhatsAppMessage::linkFor($order->fresh(), WhatsAppMessage::PAID))->toBeNull();
    $order->update(['grand_total_invoiced' => 5, 'base_grand_total_invoiced' => 5]);
    expect(WhatsAppMessage::linkFor($order->fresh(), WhatsAppMessage::PAID))->toBeNull();
    $order->update(['grand_total_invoiced' => 10, 'base_grand_total_invoiced' => 10]);
    expect(WhatsAppMessage::linkFor($order->fresh(), WhatsAppMessage::PAID))->not->toBeNull();
    $order->update(['status' => Order::STATUS_CANCELED]);
    expect(WhatsAppMessage::linkFor($order->fresh(), WhatsAppMessage::PAID))->toBeNull();
})->with(['duitnowqr_manual', 'banktransfer', 'cryptotransfer']);
