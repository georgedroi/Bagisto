<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Sales\Exceptions\InvalidRefundQuantityException;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderAddressRepository;
use Webkul\Sales\Repositories\OrderItemRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\RefundRepository;
use Webkul\Sales\Repositories\ShipmentRepository;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

/**
 * Build and inspect synthetic sales fixtures through repository boundaries.
 */
class BackendIntegrityFixtureRepository extends OrderRepository
{
    /**
     * Create a verified physical order with managed inventory and two units.
     */
    public function fixture(string $method): array
    {
        $channel = core()->getDefaultChannel();
        $source = $channel->inventory_sources->firstOrFail();
        $products = app(ProductRepository::class);
        $product = $products->create([
            'type' => 'simple',
            'attribute_family_id' => app(AttributeFamilyRepository::class)->firstOrFail()->id,
            'sku' => 'BACKEND-AUDIT-'.bin2hex(random_bytes(6)),
        ]);
        $product = $products->update([
            'channel' => $channel->code,
            'locale' => 'en',
            'sku' => $product->sku,
            'name' => 'Synthetic audit item',
            'url_key' => strtolower($product->sku),
            'price' => 10,
            'status' => 1,
            'visible_individually' => 1,
            'manage_stock' => 1,
            'weight' => 1,
            'inventories' => [$source->id => 100],
        ], $product->id);
        $order = $this->model->create([
            'increment_id' => 'BACKEND-'.bin2hex(random_bytes(8)),
            'status' => 'pending',
            'workflow_status' => 'confirmed',
            'is_guest' => 1,
            'customer_email' => 'backend-audit@example.test',
            'customer_first_name' => 'Synthetic',
            'customer_last_name' => 'Audit',
            'channel_id' => $channel->id,
            'channel_type' => get_class($channel),
            'channel_name' => 'Synthetic audit channel',
            'total_item_count' => 1,
            'total_qty_ordered' => 2,
            'base_currency_code' => core()->getBaseCurrencyCode(),
            'channel_currency_code' => core()->getBaseCurrencyCode(),
            'order_currency_code' => core()->getBaseCurrencyCode(),
            'grand_total' => 20,
            'base_grand_total' => 20,
            'sub_total' => 20,
            'base_sub_total' => 20,
            'sub_total_incl_tax' => 20,
            'base_sub_total_incl_tax' => 20,
            'shipping_method' => 'free_free',
            'shipping_title' => 'Synthetic audit shipping',
        ]);
        $order->payment()->create(['method' => $method, 'method_title' => 'Synthetic manual payment']);

        foreach ([OrderAddress::ADDRESS_TYPE_BILLING, OrderAddress::ADDRESS_TYPE_SHIPPING] as $type) {
            app(OrderAddressRepository::class)->create([
                'order_id' => $order->id,
                'address_type' => $type,
                'first_name' => 'Synthetic',
                'last_name' => 'Audit',
                'address' => 'Synthetic QA address',
                'city' => 'QA City',
                'state' => 'Selangor',
                'country' => 'MY',
                'postcode' => '00000',
                'email' => 'backend-audit@example.test',
                'phone' => '0000000000',
            ]);
        }

        $item = $this->orderItemRepository->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => get_class($product),
            'name' => 'Synthetic audit item',
            'sku' => $product->sku,
            'type' => 'simple',
            'qty_ordered' => 2,
            'weight' => 1,
            'price' => 10,
            'base_price' => 10,
            'price_incl_tax' => 10,
            'base_price_incl_tax' => 10,
            'total' => 20,
            'base_total' => 20,
            'total_incl_tax' => 20,
            'base_total_incl_tax' => 20,
            'additional' => [],
        ]);
        $this->orderItemRepository->manageInventory($item);

        return ['order' => $order->id, 'item' => $item->id, 'product' => $product->id, 'source' => $source->id];
    }

    /**
     * Read the complete mutable accounting and inventory state for a fixture.
     */
    public function snapshot(array $fixture): array
    {
        $order = $this->findOrFail($fixture['order']);
        $item = app(OrderItemRepository::class)->findOrFail($fixture['item']);
        $product = app(ProductRepository::class)->findOrFail($fixture['product']);

        return [
            'invoiceCount' => $order->invoices()->count(),
            'shipmentCount' => $order->shipments()->count(),
            'invoiced' => $item->qty_invoiced,
            'shipped' => $item->qty_shipped,
            'baseInvoiced' => $order->base_grand_total_invoiced,
            'physical' => $product->inventories()->sum('qty'),
            'reserved' => $product->ordered_inventories()->sum('qty'),
            'refundCount' => $order->refunds()->count(),
            'refunded' => $item->qty_refunded,
            'baseRefunded' => $order->base_grand_total_refunded,
        ];
    }

    /**
     * Create a fixture whose line has been invoiced for the given quantity.
     */
    public function invoiced(string $method, int $qty = 2): array
    {
        $fixture = $this->fixture($method);

        app(InvoiceRepository::class)->create(['order_id' => $fixture['order'], 'invoice' => ['items' => [$fixture['item'] => $qty]]]);

        return $fixture;
    }

    /**
     * Create an administrator for endpoint-level relationship guards.
     */
    public function administrator(?array $permissions = null): mixed
    {
        $role = app(RoleRepository::class)->create(['name' => 'Synthetic backend audit', 'permission_type' => $permissions === null ? 'all' : 'custom', 'permissions' => $permissions ?? []]);

        return app(AdminRepository::class)->create(['name' => 'Synthetic backend audit', 'email' => bin2hex(random_bytes(5)).'@example.test', 'password' => bcrypt(bin2hex(random_bytes(20))), 'status' => 1, 'role_id' => $role->id]);
    }

    /**
     * Observe a nested operation without allowing it to consume its caller's transaction.
     */
    public function transactionProbe(Closure $operation): array
    {
        $connection = $this->model->getConnection();
        $initialLevel = $connection->transactionLevel();
        $connection->beginTransaction();
        $expectedLevel = $connection->transactionLevel();

        try {
            try {
                $operation();
            } catch (RuntimeException $exception) {
                $message = $exception->getMessage();
            }

            return ['expected' => $expectedLevel, 'actual' => $connection->transactionLevel(), 'exception' => $message ?? null];
        } finally {
            if ($connection->transactionLevel() > $initialLevel) {
                $connection->rollBack($initialLevel);
            }
        }
    }
}

beforeEach(function () {
    Mail::fake();
});

it('refuses foreign invoice lines without changing either order or its inventory', function (string $method) {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->fixture($method);
    $foreign = $fixtures->fixture($method);
    $before = [$fixtures->snapshot($target), $fixtures->snapshot($foreign)];

    expect(fn () => app(InvoiceRepository::class)->create(['order_id' => $target['order'], 'invoice' => ['items' => [$foreign['item'] => 1]]]))->toThrow(ValidationException::class);

    expect([$fixtures->snapshot($target), $fixtures->snapshot($foreign)])->toBe($before);
})->with(['banktransfer', 'duitnowqr_manual']);

it('refuses foreign shipment lines without changing either order or its inventory', function (string $method) {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->fixture($method);
    $foreign = $fixtures->fixture($method);
    $before = [$fixtures->snapshot($target), $fixtures->snapshot($foreign)];

    expect(fn () => app(ShipmentRepository::class)->create(['order_id' => $target['order'], 'shipment' => ['source' => $target['source'], 'carrier_title' => 'Synthetic carrier', 'track_number' => 'SYNTHETIC-NOT-REAL', 'items' => [$foreign['item'] => [$target['source'] => 1]]]]))->toThrow(ValidationException::class);

    expect([$fixtures->snapshot($target), $fixtures->snapshot($foreign)])->toBe($before);
})->with(['banktransfer', 'duitnowqr_manual']);

it('refuses a foreign invoice line through the authenticated admin endpoint', function () {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->fixture('banktransfer');
    $foreign = $fixtures->fixture('banktransfer');
    $before = [$fixtures->snapshot($target), $fixtures->snapshot($foreign)];
    $this->loginAsAdmin($fixtures->administrator());

    $this->postJson(route('admin.sales.invoices.store', $target['order']), ['invoice' => ['items' => [$foreign['item'] => 1]]])
        ->assertRedirect()
        ->assertSessionHas('error', trans('admin::app.sales.invoices.create.invalid-qty'));

    expect([$fixtures->snapshot($target), $fixtures->snapshot($foreign)])->toBe($before);
});

it('refuses an unknown invoice line through the authenticated admin endpoint', function () {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->fixture('banktransfer');
    $before = $fixtures->snapshot($target);
    $this->loginAsAdmin($fixtures->administrator());

    $this->postJson(route('admin.sales.invoices.store', $target['order']), ['invoice' => ['items' => [PHP_INT_MAX => 1]]])
        ->assertRedirect()
        ->assertSessionHas('error', trans('admin::app.sales.invoices.create.invalid-qty'));

    expect($fixtures->snapshot($target))->toBe($before);
});

it('refuses a foreign shipment line through the authenticated admin endpoint', function () {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->invoiced('banktransfer');
    $foreign = $fixtures->invoiced('banktransfer');
    $before = [$fixtures->snapshot($target), $fixtures->snapshot($foreign)];
    $this->loginAsAdmin($fixtures->administrator());

    $this->postJson(route('admin.sales.shipments.store', $target['order']), ['shipment' => ['source' => $target['source'], 'carrier_title' => 'Synthetic carrier', 'track_number' => 'SYNTHETIC-NOT-REAL', 'items' => [$foreign['item'] => [$target['source'] => 1]]]])
        ->assertRedirect()
        ->assertSessionHas('error', trans('admin::app.sales.shipments.create.quantity-invalid'));

    expect([$fixtures->snapshot($target), $fixtures->snapshot($foreign)])->toBe($before);
});

it('refuses foreign refund lines without changing either order or its inventory', function (string $method) {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->invoiced($method);
    $foreign = $fixtures->invoiced($method);
    $before = [$fixtures->snapshot($target), $fixtures->snapshot($foreign)];

    expect(fn () => app(RefundRepository::class)->create(['order_id' => $target['order'], 'refund' => ['items' => [$foreign['item'] => 1], 'shipping' => 0, 'adjustment_refund' => 0, 'adjustment_fee' => 0]]))->toThrow(ValidationException::class);

    expect([$fixtures->snapshot($target), $fixtures->snapshot($foreign)])->toBe($before);
})->with(['banktransfer', 'duitnowqr_manual']);

it('refuses a foreign refund line in the refund summary', function () {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->invoiced('banktransfer');
    $foreign = $fixtures->invoiced('banktransfer');

    expect(fn () => app(RefundRepository::class)->getOrderItemsRefundSummary(['items' => [$foreign['item'] => 1], 'shipping' => 0, 'adjustment_refund' => 0, 'adjustment_fee' => 0], $target['order']))->toThrow(InvalidRefundQuantityException::class);
});

it('refuses a foreign refund line through the authenticated admin endpoints', function () {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->invoiced('banktransfer');
    $foreign = $fixtures->invoiced('banktransfer');
    $before = [$fixtures->snapshot($target), $fixtures->snapshot($foreign)];
    $this->loginAsAdmin($fixtures->administrator());
    $refund = ['items' => [$foreign['item'] => 1], 'shipping' => 0, 'adjustment_refund' => 0, 'adjustment_fee' => 0];

    $this->postJson(route('admin.sales.refunds.update_totals', $target['order']), $refund)->assertBadRequest();

    $this->postJson(route('admin.sales.refunds.store', $target['order']), ['refund' => $refund])
        ->assertRedirect()
        ->assertSessionHas('error', trans('admin::app.sales.refunds.create.invalid-qty'));

    expect([$fixtures->snapshot($target), $fixtures->snapshot($foreign)])->toBe($before);
});

it('refuses a refund quantity above the invoiced quantity not yet refunded', function () {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->invoiced('banktransfer', 1);
    $before = $fixtures->snapshot($target);
    $this->loginAsAdmin($fixtures->administrator());
    $refund = fn (int $qty) => ['refund' => ['items' => [$target['item'] => $qty], 'shipping' => 0, 'adjustment_refund' => 0, 'adjustment_fee' => 0]];

    $this->postJson(route('admin.sales.refunds.store', $target['order']), $refund(2))
        ->assertRedirect()
        ->assertSessionHas('error', trans('admin::app.sales.refunds.create.invalid-qty'));

    expect($fixtures->snapshot($target))->toBe($before);

    $this->postJson(route('admin.sales.refunds.store', $target['order']), $refund(1))->assertRedirect(route('admin.sales.orders.view', $target['order']));
    $afterFirst = $fixtures->snapshot($target);

    expect($afterFirst['refundCount'])->toBe(1)->and((int) $afterFirst['refunded'])->toBe(1);

    $this->postJson(route('admin.sales.refunds.store', $target['order']), $refund(1))->assertRedirect();

    expect($fixtures->snapshot($target))->toBe($afterFirst);
});

it('refuses a refund amount above the invoiced amount not yet refunded', function (array $refund) {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->invoiced('banktransfer', 1);
    $refund['items'] = isset($refund['items']) ? [$target['item'] => $refund['items']] : [];
    $before = $fixtures->snapshot($target);
    $this->loginAsAdmin($fixtures->administrator());

    $response = $this->postJson(route('admin.sales.refunds.store', $target['order']), ['refund' => $refund]);

    expect($response->status())->not->toBe(500)
        ->and($response->headers->get('Location'))->not->toBe(route('admin.sales.orders.view', $target['order']))
        ->and($fixtures->snapshot($target))->toBe($before);
})->with([
    'shipping never invoiced' => [['items' => 1, 'shipping' => 1000, 'adjustment_refund' => 0, 'adjustment_fee' => 0]],
    'adjustment offset by a fee' => [['shipping' => 0, 'adjustment_refund' => 1000, 'adjustment_fee' => 500]],
    'negative adjustment fee' => [['items' => 1, 'shipping' => 0, 'adjustment_refund' => 0, 'adjustment_fee' => -1000]],
    'negative adjustment refund' => [['items' => 1, 'shipping' => 0, 'adjustment_refund' => -1000, 'adjustment_fee' => 0]],
    'negative shipping' => [['items' => 1, 'shipping' => -5, 'adjustment_refund' => 0, 'adjustment_fee' => 0]],
]);

it('refuses a repository refund above the invoiced amount not yet refunded', function (array $refund) {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->invoiced('banktransfer', 1);
    $refund['items'] = isset($refund['items']) ? [$target['item'] => $refund['items']] : [];
    $before = $fixtures->snapshot($target);

    expect(fn () => app(RefundRepository::class)->create(['order_id' => $target['order'], 'refund' => $refund]))->toThrow(ValidationException::class);

    expect($fixtures->snapshot($target))->toBe($before);
})->with([
    'shipping never invoiced' => [['items' => 1, 'shipping' => 1000, 'adjustment_refund' => 0, 'adjustment_fee' => 0]],
    'adjustment above the remaining amount' => [['shipping' => 0, 'adjustment_refund' => 11, 'adjustment_fee' => 0]],
    'negative adjustment fee' => [['items' => 1, 'shipping' => 0, 'adjustment_refund' => 0, 'adjustment_fee' => -1000]],
]);

it('keeps valid own-order refunds and adjustment-only refunds available', function () {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->invoiced('banktransfer');
    $foreign = $fixtures->invoiced('banktransfer');
    $foreignBefore = $fixtures->snapshot($foreign);
    $this->loginAsAdmin($fixtures->administrator());

    $this->postJson(route('admin.sales.refunds.store', $target['order']), ['refund' => ['items' => [$target['item'] => 1], 'shipping' => 0, 'adjustment_refund' => 0, 'adjustment_fee' => 0]])
        ->assertRedirect(route('admin.sales.orders.view', $target['order']));
    app(RefundRepository::class)->create(['order_id' => $target['order'], 'refund' => ['items' => [], 'shipping' => 0, 'adjustment_refund' => 10, 'adjustment_fee' => 0]]);
    $after = $fixtures->snapshot($target);

    expect($after['refundCount'])->toBe(2)
        ->and((int) $after['refunded'])->toBe(1)
        ->and((float) $after['baseRefunded'])->toBe(20.0)
        ->and($fixtures->snapshot($foreign))->toBe($foreignBefore);
});

it('refuses invoice, shipment and refund creation to a role without the create permission', function (string $route, array $permissions, array $payload) {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->invoiced('banktransfer', 1);
    $before = $fixtures->snapshot($target);
    $payload = json_decode(str_replace(['ITEM', 'SOURCE'], [$target['item'], $target['source']], json_encode($payload)), true);
    $this->loginAsAdmin($fixtures->administrator($permissions));

    $this->postJson(route($route, $target['order']), $payload)->assertUnauthorized();

    expect($fixtures->snapshot($target))->toBe($before);
})->with([
    'invoice' => ['admin.sales.invoices.store', ['sales', 'sales.orders', 'sales.orders.view', 'sales.invoices', 'sales.invoices.view'], ['invoice' => ['items' => ['ITEM' => 1]]]],
    'shipment' => ['admin.sales.shipments.store', ['sales', 'sales.orders', 'sales.orders.view', 'sales.shipments', 'sales.shipments.view'], ['shipment' => ['source' => 'SOURCE', 'carrier_title' => 'Synthetic carrier', 'track_number' => 'SYNTHETIC-NOT-REAL', 'items' => ['ITEM' => ['SOURCE' => 1]]]]],
    'refund' => ['admin.sales.refunds.store', ['sales', 'sales.orders', 'sales.orders.view', 'sales.refunds', 'sales.refunds.view'], ['refund' => ['items' => ['ITEM' => 1], 'shipping' => 0, 'adjustment_refund' => 0, 'adjustment_fee' => 0]]],
    'refund totals' => ['admin.sales.refunds.update_totals', ['sales', 'sales.orders', 'sales.orders.view', 'sales.refunds', 'sales.refunds.view'], ['items' => ['ITEM' => 1], 'shipping' => 0, 'adjustment_refund' => 0, 'adjustment_fee' => 0]],
]);

it('keeps valid own-order invoice and shipment processing available', function () {
    $fixtures = app(BackendIntegrityFixtureRepository::class);
    $target = $fixtures->fixture('banktransfer');
    $foreign = $fixtures->fixture('banktransfer');
    $foreignBefore = $fixtures->snapshot($foreign);
    app(InvoiceRepository::class)->create(['order_id' => $target['order'], 'invoice' => ['items' => [$target['item'] => 1]]]);
    app(ShipmentRepository::class)->create(['order_id' => $target['order'], 'shipment' => ['source' => $target['source'], 'carrier_title' => 'Synthetic carrier', 'track_number' => 'SYNTHETIC-NOT-REAL', 'items' => [$target['item'] => [$target['source'] => 1]]]]);
    $after = $fixtures->snapshot($target);

    expect($after['invoiceCount'])->toBe(1)
        ->and($after['shipmentCount'])->toBe(1)
        ->and((int) $after['invoiced'])->toBe(1)
        ->and((int) $after['shipped'])->toBe(1)
        ->and((int) $after['physical'])->toBe(99)
        ->and((int) $after['reserved'])->toBe(1)
        ->and($fixtures->snapshot($foreign))->toBe($foreignBefore);
});

it('preserves the caller transaction after an order creation failure', function () {
    Event::listen('checkout.order.save.before', function () {
        throw new RuntimeException('Synthetic forced order failure');
    });
    $probe = app(BackendIntegrityFixtureRepository::class)->transactionProbe(fn () => app(OrderRepository::class)->createOrderIfNotThenRetry(['payment' => ['method' => 'banktransfer']], 1, 1));

    expect($probe['exception'])->toBe('Synthetic forced order failure')
        ->and($probe['actual'])->toBe($probe['expected']);
});
