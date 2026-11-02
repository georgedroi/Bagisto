<?php

use App\Models\ProductStockAlert;
use App\Models\ProductStockBatch;
use Webkul\Faker\Helpers\Product;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Product\Models\ProductFlat;
use Webkul\Product\Models\ProductInventory;
use Webkul\Product\Models\ProductReview;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderPayment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

dataset('workspace pages', ['overview', 'orders', 'shipping', 'returns', 'reviews', 'products', 'promotions', 'income', 'fees', 'settings']);

it('opens every workspace page with no marketplace content', function (string $page) {
    $this->loginAsAdmin();

    $html = get(route('admin.seller_workspace.'.$page))->assertOk()->getContent();

    expect($html)->toContain('class="msw"')
        ->and($html)->toContain('v-pre')
        ->and(strtolower($html))->not->toContain('lazada')
        ->and(strtolower($html))->not->toContain('shopee')
        ->and(strtolower($html))->not->toContain('lazcoin');
})->with('workspace pages');

it('sends guests to the login page', function (string $page) {
    get(route('admin.seller_workspace.'.$page))->assertRedirect(route('admin.session.create'));
})->with('workspace pages');

it('refuses a role without the matching permission and hides its menu entry', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['sales', 'sales.workspace']]);
    $admin = Admin::factory()->create(['role_id' => $role->id]);

    actingAs($admin, 'admin');

    $html = get(route('admin.seller_workspace.orders'))->assertOk()->getContent();

    expect($html)->not->toContain(route('admin.seller_workspace.income'));

    get(route('admin.seller_workspace.income'))->assertUnauthorized();
    get(route('admin.seller_workspace.settings'))->assertUnauthorized();
});

it('filters orders by queue and search text', function () {
    $this->loginAsAdmin();

    $packing = Order::factory()->create(['status' => 'processing', 'workflow_status' => 'packing', 'increment_id' => 'WS-PACK-1']);
    $delivered = Order::factory()->create(['status' => 'completed', 'workflow_status' => 'delivered', 'increment_id' => 'WS-DONE-1']);

    get(route('admin.seller_workspace.orders', ['queue' => 'to_ship']))
        ->assertSee($packing->increment_id)
        ->assertDontSee($delivered->increment_id);

    get(route('admin.seller_workspace.orders', ['search' => 'WS-DONE']))
        ->assertSee($delivered->increment_id)
        ->assertDontSee($packing->increment_id);

    get(route('admin.seller_workspace.orders', ['queue' => 'nonsense']))->assertRedirect();
});

it('escapes customer text and keeps it out of Vue compilation', function () {
    $this->loginAsAdmin();

    $product = (new Product)->getSimpleProductFactory()->create();

    ProductReview::factory()->create([
        'product_id' => $product->id,
        'title' => '<script>alert(1)</script>',
        'comment' => '{{ constructor.constructor("alert(1)")() }}',
        'status' => 'pending',
        'rating' => 5,
    ]);

    $html = get(route('admin.seller_workspace.reviews'))->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->and($html)->toMatch('/<div class="msw" v-pre /');
});

it('rejects a date range that ends before it starts', function () {
    $this->loginAsAdmin();

    get(route('admin.seller_workspace.fees', ['from' => '2026-10-30', 'to' => '2026-10-01']))->assertRedirect();
    get(route('admin.seller_workspace.fees', ['from' => '2026-10-01', 'to' => '2026-10-30']))->assertOk();
});

/**
 * Read a rendered action form the way a browser would: its address and its hidden fields.
 */
function workspaceForm(string $html, string $action, array $match = []): array
{
    preg_match_all('/<form[^>]*action="([^"]+)"[^>]*>(.*?)<\/form>/s', $html, $forms, PREG_SET_ORDER);

    foreach ($forms as [, $url, $body]) {
        if (html_entity_decode($url) !== $action) {
            continue;
        }

        preg_match_all('/<input type="hidden" name="([^"]+)"[^>]*value="([^"]*)"/', $body, $fields, PREG_SET_ORDER);

        $hidden = collect($fields)->mapWithKeys(fn ($field) => [$field[1] => html_entity_decode($field[2])])->all();

        if (array_intersect_assoc($match, $hidden) === $match) {
            return $hidden;
        }
    }

    throw new RuntimeException('No form for '.$action);
}

it('saves a new price through the form on the products page', function () {
    $this->loginAsAdmin();

    $product = (new Product)->getSimpleProductFactory()->create();
    $action = route('admin.catalog.product_pricing.update', $product->id);
    $html = get(route('admin.seller_workspace.products', ['search' => $product->sku]))->assertOk()->getContent();

    expect($html)->toContain(route('admin.catalog.product_pricing.show', $product->id));

    post($action, workspaceForm($html, $action) + ['price' => '123.45', 'special_price' => '', 'special_price_from' => '', 'special_price_to' => ''], ['Accept' => 'application/json'])->assertOk();

    expect((float) ProductFlat::query()->where('product_id', $product->id)->value('price'))->toBe(123.45);

    get(route('admin.seller_workspace.products', ['search' => $product->sku]))->assertSee('123.45');
});

it('saves a daily price with a date range, shows it on the list, then makes it long term', function () {
    $this->loginAsAdmin();

    $product = (new Product)->getSimpleProductFactory()->create();
    $action = route('admin.catalog.product_pricing.update', $product->id);
    $list = route('admin.seller_workspace.products', ['search' => $product->sku]);
    $hidden = workspaceForm(get($list)->assertOk()->getContent(), $action);

    post($action, array_merge($hidden, ['price' => '229', 'special_price' => '149', 'special_price_from' => now()->toDateString(), 'special_price_to' => now()->addWeek()->toDateString()]), ['Accept' => 'application/json'])->assertOk();

    get($list)->assertSee('149')->assertSee('<s class="msw-was"', false);

    post($action, array_merge($hidden, ['price' => '229', 'special_price' => '149', 'special_price_from' => '', 'special_price_to' => '']), ['Accept' => 'application/json'])->assertOk();

    $flat = ProductFlat::query()->where('product_id', $product->id)->first();

    expect((float) $flat->special_price)->toBe(149.0)
        ->and($flat->special_price_from)->toBeNull()
        ->and($flat->special_price_to)->toBeNull();

    post($action, array_merge($hidden, ['price' => '229', 'special_price' => '300']), ['Accept' => 'application/json'])->assertUnprocessable();
});

it('does not show a daily price on the list once its dates have passed', function () {
    $this->loginAsAdmin();

    $product = (new Product)->getSimpleProductFactory()->create();
    $action = route('admin.catalog.product_pricing.update', $product->id);
    $list = route('admin.seller_workspace.products', ['search' => $product->sku]);

    post($action, array_merge(workspaceForm(get($list)->getContent(), $action), ['price' => '229', 'special_price' => '149', 'special_price_from' => now()->subWeek()->toDateString(), 'special_price_to' => now()->subDay()->toDateString()]), ['Accept' => 'application/json'])->assertOk();

    get($list)->assertDontSee('<s class="msw-was"', false);
});

it('copies and deletes a product through the forms on the products page', function () {
    $this->loginAsAdmin();

    $product = (new Product)->getSimpleProductFactory()->create();
    $list = route('admin.seller_workspace.products', ['search' => $product->sku]);
    $copy = route('admin.catalog.products.copy', $product->id);
    $delete = route('admin.catalog.products.delete', $product->id);
    $html = get($list)->assertOk()->getContent();

    post($copy, workspaceForm($html, $copy), ['Accept' => 'application/json'])->assertOk();
    post($delete, workspaceForm($html, $delete), ['Accept' => 'application/json'])->assertOk();

    expect(ProductFlat::query()->where('product_id', $product->id)->exists())->toBeFalse();
});

it('raises a stock alert below its level, filters by it and removes it when switched off', function () {
    $this->loginAsAdmin();

    $product = (new Product)->getSimpleProductFactory()->create();
    $other = (new Product)->getSimpleProductFactory()->create();

    ProductFlat::query()->where('product_id', $product->id)->update(['quantity' => 5]);
    ProductFlat::query()->where('product_id', $other->id)->update(['quantity' => 5]);

    $action = route('admin.catalog.product_stock.alert', $product->id);
    $badge = '<span class="msw-alert-badge">';
    $list = route('admin.seller_workspace.products', ['search' => $product->sku]);
    $hidden = workspaceForm(get($list)->assertOk()->getContent(), $action);

    post($action, $hidden + ['enabled' => 1, 'threshold' => 0], ['Accept' => 'application/json'])->assertUnprocessable();

    post($action, $hidden + ['enabled' => 1, 'threshold' => 5], ['Accept' => 'application/json'])->assertOk();
    get($list)->assertDontSee($badge, false);

    post($action, $hidden + ['enabled' => 1, 'threshold' => 6], ['Accept' => 'application/json'])->assertOk();
    get($list)->assertSee($badge, false);

    expect(ProductStockAlert::query()->where('product_id', $product->id)->count())->toBe(1);

    get(route('admin.seller_workspace.products', ['alert' => 1]))->assertSee($product->sku)->assertDontSee($other->sku);

    post($action, $hidden + ['threshold' => 6], ['Accept' => 'application/json'])->assertOk();

    expect(ProductStockAlert::query()->where('product_id', $product->id)->exists())->toBeFalse();
});

it('saves dated batches with the stock, keeps them out of sellable stock and replaces them on the next save', function () {
    $this->loginAsAdmin();

    $product = (new Product)->getSimpleProductFactory()->create();
    $source = InventorySource::query()->where('status', 1)->firstOrFail();
    $action = route('admin.catalog.product_stock.update', $product->id);
    $json = ['Accept' => 'application/json'];
    $hidden = workspaceForm(get(route('admin.seller_workspace.products', ['search' => $product->sku]))->assertOk()->getContent(), $action);
    $batch = ['quantity' => 7, 'ship_from' => '2026-11-08', 'ship_to' => '2026-12-01'];

    post($action, $hidden + ['inventories' => [$source->id => 43], 'batches' => [$source->id => [array_merge($batch, ['ship_to' => '2026-11-01'])]]], $json)->assertUnprocessable();
    post($action, $hidden + ['inventories' => [$source->id => 43], 'batches' => [$source->id => [array_fill(0, 1, $batch)[0], $batch, $batch, $batch, $batch, $batch]]], $json)->assertUnprocessable();

    expect(ProductStockBatch::query()->where('product_id', $product->id)->exists())->toBeFalse();

    post($action, $hidden + ['inventories' => [$source->id => 43], 'batches' => [$source->id => [$batch]]], $json)->assertOk();

    get(route('admin.catalog.product_stock.show', $product->id))
        ->assertJsonPath('data.total', 43)
        ->assertJsonPath('data.sources.0.batches.0.ship_to', '2026-12-01');

    post($action, array_diff_key($hidden, ['batches_sync' => 1]) + ['inventories' => [$source->id => 40]], $json)->assertOk();

    expect(ProductStockBatch::query()->where('product_id', $product->id)->count())->toBe(1);

    post($action, $hidden + ['inventories' => [$source->id => 40]], $json)->assertOk();

    expect(ProductStockBatch::query()->where('product_id', $product->id)->exists())->toBeFalse();
});

it('switches a product off and on through the status form and hides the form without edit permission', function () {
    $this->loginAsAdmin();

    $product = (new Product)->getSimpleProductFactory()->create();
    $action = route('admin.seller_workspace.product_lifecycle', $product->id);
    $list = route('admin.seller_workspace.products', ['search' => $product->sku]);
    $status = fn () => (int) ProductFlat::query()->where('product_id', $product->id)->value('status');

    ProductFlat::query()->where('product_id', $product->id)->update(['status' => 1]);

    $html = get($list)->assertOk()->getContent();

    expect($html)->toContain('role="switch"');

    post($action, workspaceForm($html, $action))->assertRedirect();

    expect($status())->toBe(0);

    post($action, workspaceForm(get($list)->getContent(), $action))->assertRedirect();

    expect($status())->toBe(1);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['catalog', 'catalog.products', 'catalog.products.workspace']]);

    actingAs(Admin::factory()->create(['role_id' => $role->id]), 'admin');

    expect(get($list)->assertOk()->getContent())->not->toContain('role="switch"');

    post($action, ['state' => 'offline'])->assertForbidden();

    expect($status())->toBe(1);
});

it('shows a running special price with red day-first dates, a bold price and the old price in grey', function () {
    $this->loginAsAdmin();

    $product = (new Product)->getSimpleProductFactory()->create();

    ProductFlat::query()->where('product_id', $product->id)->update(['status' => 1, 'price' => 229, 'special_price' => 149, 'special_price_from' => now()->toDateString(), 'special_price_to' => now()->addDays(3)->toDateString()]);

    $html = get(route('admin.seller_workspace.products', ['search' => $product->sku]))->assertOk()->getContent();

    expect($html)->toContain('<small class="msw-deal-dates">'.now()->format('d-m-Y').' ~ '.now()->addDays(3)->format('d-m-Y').'</small>')
        ->and($html)->toMatch('/<span class="msw-price">[^<]*149\.00<\/span>/')
        ->and($html)->toMatch('/<s class="msw-was">[^<]*229\.00<\/s>/');
});

it('saves new stock through the form on the products page and refuses a negative quantity', function () {
    $this->loginAsAdmin();

    $product = (new Product)->getSimpleProductFactory()->create();
    $source = InventorySource::query()->where('status', 1)->firstOrFail();
    $action = route('admin.catalog.product_stock.update', $product->id);
    $html = get(route('admin.seller_workspace.products', ['search' => $product->sku]))->assertOk()->getContent();
    $hidden = workspaceForm($html, $action);

    post($action, $hidden + ['inventories' => [$source->id => -1]], ['Accept' => 'application/json'])->assertUnprocessable();
    post($action, $hidden + ['inventories' => [$source->id => 42]], ['Accept' => 'application/json'])->assertOk();

    expect((int) ProductInventory::query()->where(['product_id' => $product->id, 'inventory_source_id' => $source->id])->value('qty'))->toBe(42);
});

it('approves a review through the form on the reviews page', function () {
    $this->loginAsAdmin();

    $product = (new Product)->getSimpleProductFactory()->create();
    $review = ProductReview::factory()->create(['product_id' => $product->id, 'status' => 'pending', 'rating' => 4]);
    $action = route('admin.customers.customers.review.update', $review->id);
    $html = get(route('admin.seller_workspace.reviews', ['status' => 'pending']))->assertOk()->getContent();

    post($action, workspaceForm($html, $action, ['status' => 'approved']), ['Accept' => 'application/json'])->assertOk();

    expect($review->fresh()->status)->toBe('approved');
});

it('moves an order one step through the orders page and refuses a stale or skipped step', function () {
    $this->loginAsAdmin();

    $order = Order::factory()->create(['status' => 'processing', 'workflow_status' => 'confirmed']);

    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'duitnowqr_manual']);

    $action = route('admin.sales.orders.workflow.update', $order->id);
    $html = get(route('admin.seller_workspace.orders', ['search' => $order->increment_id]))->assertOk()->getContent();
    $hidden = workspaceForm($html, $action, ['workflow_status' => 'packing']);

    post($action, ['workflow_status' => 'delivered'] + $hidden, ['Accept' => 'application/json'])->assertStatus(422);
    post($action, ['expected_status' => 'packing'] + $hidden, ['Accept' => 'application/json'])->assertStatus(409);
    post($action, $hidden)->assertRedirect(route('admin.sales.orders.view', $order->id));

    expect($order->fresh()->workflow_status)->toBe('packing');

    $next = get(route('admin.seller_workspace.orders', ['search' => $order->increment_id]))->assertOk()->getContent();
    $step = route('admin.sales.orders.workflow.update', $order->id);

    post($step, workspaceForm($next, $step, ['workflow_status' => 'ready_to_ship']), ['Accept' => 'application/json'])->assertStatus(422);

    expect($order->fresh()->workflow_status)->toBe('packing');
});

it('hides action forms from a role without those permissions and the engine refuses them', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['sales', 'sales.workspace', 'catalog', 'catalog.products', 'catalog.products.workspace', 'customers', 'customers.reviews', 'customers.reviews.workspace']]);

    actingAs(Admin::factory()->create(['role_id' => $role->id]), 'admin');

    $product = (new Product)->getSimpleProductFactory()->create();
    $review = ProductReview::factory()->create(['product_id' => $product->id, 'status' => 'pending', 'rating' => 4]);
    $order = Order::factory()->create(['status' => 'processing', 'workflow_status' => 'confirmed']);

    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'duitnowqr_manual']);

    expect(get(route('admin.seller_workspace.products', ['search' => $product->sku]))->assertOk()->getContent())->not->toContain('data-msw-json')
        ->and(get(route('admin.seller_workspace.reviews'))->assertOk()->getContent())->not->toContain('data-msw-json')
        ->and(get(route('admin.seller_workspace.orders'))->assertOk()->getContent())->not->toContain('data-msw-json');

    post(route('admin.customers.customers.review.update', $review->id), ['_method' => 'PUT', 'status' => 'approved'], ['Accept' => 'application/json'])->assertUnauthorized();

    expect($review->fresh()->status)->toBe('pending');
});

it('opens every link on the settings page', function () {
    $this->loginAsAdmin();

    $html = get(route('admin.seller_workspace.settings'))->assertOk()->getContent();

    preg_match_all('/class="msw-set" href="([^"]+)"/', $html, $matches);

    expect($matches[1])->toHaveCount(12);

    foreach ($matches[1] as $url) {
        get(html_entity_decode($url))->assertOk();
    }
});

it('links to the workspace from the admin dashboard', function () {
    $this->loginAsAdmin();

    get(route('admin.dashboard.index'))->assertSee(route('admin.seller_workspace.overview'));
});
