<?php

use App\Repositories\OrderQueueRepository;
use App\Repositories\ProductLifecycleRepository;
use App\Repositories\ProductStockRepository;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Models\ProductFlat;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Sales\Models\Shipment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

it('connects the dashboard to the fulfillment queues and category-first product entry', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.dashboard.index'))->assertOk();

    foreach (['awaiting_payment', 'to_ship', 'shipping', 'failed_delivery', 'returns'] as $queue) {
        $response->assertSee(route('admin.sales.orders.index', ['queue' => $queue]));
    }

    $response->assertSee(route('admin.seller_workspace.add_product'));
});

it('filters operational queues without treating a completed Bagisto order as delivered', function () {
    $packing = Order::factory()->create(['status' => 'processing', 'workflow_status' => 'packing']);
    $transit = Order::factory()->create(['status' => 'completed', 'workflow_status' => 'in_transit']);
    $delivered = Order::factory()->create(['status' => 'completed', 'workflow_status' => 'delivered']);
    $failed = Order::factory()->create(['status' => 'completed', 'workflow_status' => 'failed_to_deliver']);
    $cancelled = Order::factory()->create(['status' => 'canceled', 'workflow_status' => 'packing']);

    foreach ([$packing, $transit, $delivered, $failed, $cancelled] as $order) {
        OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'duitnowqr_manual']);
    }

    $this->loginAsAdmin();

    expect(app(OrderQueueRepository::class)->queueCounts())->toBe([
        'awaiting_payment' => 0,
        'to_ship' => 1,
        'shipping' => 1,
        'failed_delivery' => 1,
        'delivered' => 1,
        'returns' => 1,
    ]);

    foreach (['to_ship' => $packing, 'shipping' => $transit, 'delivered' => $delivered, 'failed_delivery' => $failed, 'returns' => $cancelled] as $queue => $expected) {
        $records = getJson(route('admin.sales.orders.index', ['queue' => $queue]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('records');

        expect(collect($records)->pluck('id')->all())->toBe([$expected->id]);
    }
});

it('shows saved fulfillment progress in the dashboard recent order list', function () {
    $order = Order::factory()->create(['status' => 'processing', 'workflow_status' => 'delivered']);
    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'duitnowqr_manual']);
    $this->loginAsAdmin();

    $html = get(route('admin.dashboard.index'))->assertOk()->getContent();
    preg_match('/<tr data-entity="order" data-id="'.$order->increment_id.'">([\s\S]*?)<\/tr>/', $html, $row);

    expect(strip_tags($row[1] ?? ''))->toContain(trans('admin::order-status.delivered'));
});

it('keeps ordinary manual-payment shipments in the fulfillment queues', function () {
    $packing = Order::factory()->create(['status' => 'processing']);
    $shipping = Order::factory()->create(['status' => 'completed']);
    Shipment::factory()->create(['order_id' => $shipping->id]);
    $this->loginAsAdmin();

    foreach (['to_ship' => $packing, 'shipping' => $shipping] as $queue => $expected) {
        $records = getJson(route('admin.sales.orders.index', ['queue' => $queue]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('records');

        expect(collect($records)->pluck('id')->all())->toBe([$expected->id]);
    }
});

it('preserves search and workflow filters when navigating order queues', function () {
    $this->loginAsAdmin();

    get(route('admin.sales.orders.index', ['queue' => 'to_ship', 'workflow' => 'packing', 'search' => 'QA-order']))
        ->assertOk()
        ->assertSee('name="queue" value="to_ship"', false)
        ->assertSee('name="search" value="QA-order"', false)
        ->assertSee(route('admin.sales.orders.index', ['workflow' => 'packing', 'search' => 'QA-order', 'queue' => 'shipping']));
});

it('opens category-first product entry from the catalog primary action', function () {
    $this->loginAsAdmin();

    $html = get(route('admin.catalog.products.index'))->assertOk()->getContent();
    preg_match('/<a[^>]*href="([^"]+)"[^>]*data-action="create-product"/', $html, $action);

    expect($action[1] ?? null)->toBe(route('admin.seller_workspace.add_product'));
});

it('hides unauthorized pricing actions and keeps the native creation fallback', function () {
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions' => ['catalog', 'catalog.products', 'catalog.products.create'],
    ]);
    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    $response = get(route('admin.catalog.products.index'))->assertOk()
        ->assertDontSee('data-action="edit-pricing"', false)
        ->assertDontSee('v-product-pricing', false);

    preg_match('/<a[^>]*href="([^"]+)"[^>]*data-action="create-product"/', $response->getContent(), $action);

    expect($action[1] ?? null)->toBe(route('admin.catalog.products.create'));

    get(route('admin.catalog.product_entry.index'))->assertUnauthorized();
    getJson(route('admin.catalog.product_pricing.show', 1))->assertUnauthorized();
});

it('shows task list counts that match the order list each one opens', function () {
    $before = app(OrderQueueRepository::class)->workflowCounts();

    Order::factory()->create(['status' => 'pending', 'workflow_status' => 'awaiting_verification']);
    Order::factory()->create(['status' => 'processing', 'workflow_status' => 'packing']);
    Order::factory()->create(['status' => 'canceled', 'workflow_status' => 'packing']);

    $this->loginAsAdmin();

    $after = app(OrderQueueRepository::class)->workflowCounts();

    expect($after['awaiting_verification'])->toBe($before['awaiting_verification'] + 1)
        ->and($after['packing'])->toBe($before['packing'] + 1);

    $response = get(route('admin.dashboard.index'))->assertOk();

    foreach (OrderQueueRepository::WORKFLOW_TASKS as $stages) {
        foreach ($stages as $stage) {
            $response->assertSee(route('admin.sales.orders.index', ['workflow' => $stage]));

            $total = getJson(route('admin.sales.orders.index', ['workflow' => $stage]), ['X-Requested-With' => 'XMLHttpRequest'])
                ->assertOk()->json('meta.total');

            expect($total)->toBe($after[$stage]);
        }
    }
});

it('shows product task counts that match the product list each one opens', function () {
    $stock = app(ProductStockRepository::class);
    $before = $stock->stockCounts();

    $cases = [
        ['status' => 1, 'manage_stock' => 1, 'quantity' => 0],
        ['status' => 1, 'manage_stock' => 1, 'quantity' => null],
        ['status' => 1, 'manage_stock' => 1, 'quantity' => 4],
        ['status' => 0, 'manage_stock' => 1, 'quantity' => 50],
        ['status' => 1, 'manage_stock' => 0, 'quantity' => 0],
        ['status' => 1, 'manage_stock' => 1, 'quantity' => 50],
    ];

    foreach ($cases as $values) {
        $product = (new ProductFaker)->getSimpleProductFactory()->create();

        ProductFlat::where('product_id', $product->id)->update($values);
    }

    $this->loginAsAdmin();

    $after = $stock->stockCounts();

    expect($after['out'])->toBe($before['out'] + 2)
        ->and($after['low'])->toBe($before['low'] + 1)
        ->and($after['inactive'])->toBe($before['inactive'] + 1);

    $response = get(route('admin.dashboard.index'))->assertOk();

    foreach (array_keys(ProductStockRepository::FILTERS) as $filter) {
        $response->assertSee(route('admin.catalog.products.index', ['stock' => $filter]));

        $total = getJson(route('admin.catalog.products.index', ['stock' => $filter]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('meta.total');

        expect($total)->toBe($after[$filter]);
    }
});

it('counts offline products as low or out of stock but leaves obsolete products out', function () {
    $stock = app(ProductStockRepository::class);
    $before = $stock->stockCounts();

    $make = function (array $values): int {
        $product = (new ProductFaker)->getSimpleProductFactory()->create();

        ProductFlat::where('product_id', $product->id)->update($values);

        return $product->id;
    };

    $make(['status' => 0, 'manage_stock' => 1, 'quantity' => 9]);
    $make(['status' => 0, 'manage_stock' => 1, 'quantity' => 0]);

    app(ProductLifecycleRepository::class)->saveState($make(['status' => 0, 'manage_stock' => 1, 'quantity' => 3]), ProductLifecycleRepository::OBSOLETE);
    app(ProductLifecycleRepository::class)->saveState($make(['status' => 0, 'manage_stock' => 1, 'quantity' => 0]), ProductLifecycleRepository::OBSOLETE);

    $this->loginAsAdmin();

    $after = $stock->stockCounts();

    expect($after['low'])->toBe($before['low'] + 1)
        ->and($after['out'])->toBe($before['out'] + 1);

    foreach (['low', 'out'] as $filter) {
        $total = getJson(route('admin.catalog.products.index', ['stock' => $filter]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('meta.total');

        expect($total)->toBe($after[$filter]);
    }
});

it('serves the add product form on the workspace layout with nothing from marketplace pages', function () {
    $this->loginAsAdmin();

    $html = get(route('admin.seller_workspace.add_product'))->assertOk()->getContent();

    expect($html)->toContain('id="msw-entry"')
        ->and($html)->toContain('class="msw"')
        ->and($html)->toContain('product-entry')
        ->and($html)->toContain(csrf_token())
        ->and($html)->toContain('Next: Complete Product Info')
        ->and($html)->not->toContain('%%')
        ->and(strtolower($html))->not->toContain('shopee')
        ->and(strtolower($html))->not->toContain('lazada')
        ->and($html)->not->toContain('GTIN');

    get(route('admin.seller_workspace.products'))->assertSee(route('admin.seller_workspace.add_product'));
});

it('refuses the add product page to a role that cannot add products', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard', 'catalog', 'catalog.products']]);
    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    get(route('admin.seller_workspace.add_product'))->assertUnauthorized();
});

it('refuses the manage products page without product permission', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard', 'sales', 'sales.orders']]);
    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    get(route('admin.seller_workspace.products'))->assertUnauthorized();
});

it('ignores an unknown stock filter on the product list', function () {
    $this->loginAsAdmin();

    $all = getJson(route('admin.catalog.products.index'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('meta.total');
    $unknown = getJson(route('admin.catalog.products.index', ['stock' => 'x\' OR 1=1']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('meta.total');

    expect($unknown)->toBe($all);
});

it('hides product task counts from a role without catalog access', function () {
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions' => ['dashboard', 'sales', 'sales.orders'],
    ]);
    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    get(route('admin.dashboard.index'))->assertOk()
        ->assertSee(route('admin.sales.orders.index', ['workflow' => 'packing']))
        ->assertDontSee('data-field="product.count.out"', false);
});
