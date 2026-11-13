<?php

use Webkul\Sales\Models\Order;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

it('uses the workspace layout and the same figures at both existing home addresses', function () {
    $this->loginAsAdmin();

    $filters = ['start' => now()->subDays(7)->toDateString(), 'end' => now()->toDateString()];
    $dashboard = get(route('admin.dashboard.index', $filters))->assertOk()
        ->assertViewIs('manpleasure-admin::seller-workspace.page');
    $workspace = get(route('admin.seller_workspace.overview', $filters))->assertOk()
        ->assertViewIs('manpleasure-admin::seller-workspace.page');

    expect($dashboard->viewData('business')['range'])->toBe($workspace->viewData('business')['range'])
        ->and($dashboard->viewData('stages'))->toBe($workspace->viewData('stages'));

    foreach ([$dashboard, $workspace] as $response) {
        $response->assertSee('class="msw"', false)
            ->assertSee('data-msw-page="overview"', false)
            ->assertSee('data-field="business.range-sales"', false)
            ->assertSee('class="msw-bars"', false)
            ->assertDontSee('v-dashboard-filters', false)
            ->assertDontSee(trans('admin::seller-workspace.menu.admin'));
    }
});

it('opens the short workspace address without changing the existing home permissions', function () {
    $this->loginAsAdmin();

    get(url(config('app.admin_url').'/workspace'))->assertOk()
        ->assertViewIs('manpleasure-admin::seller-workspace.page');
});

it('keeps dashboard-only admins on an accessible home and hides operational details', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);
    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    get(route('admin.dashboard.index'))->assertOk()
        ->assertSee('data-field="business.range-sales"', false)
        ->assertSee('action="'.route('admin.dashboard.index').'"', false)
        ->assertSee('href="'.route('admin.dashboard.index').'"', false)
        ->assertDontSee('class="msw-task"', false)
        ->assertDontSee('class="msw-order-row"', false)
        ->assertDontSee(route('admin.seller_workspace.overview'))
        ->assertDontSee(route('admin.seller_workspace.income'));

    get(route('admin.seller_workspace.orders'))->assertUnauthorized();
    get(route('admin.seller_workspace.overview'))->assertUnauthorized();
});

it('keeps the sales workspace usable without exposing dashboard figures or finance links', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['sales', 'sales.workspace']]);
    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    get(route('admin.seller_workspace.overview'))->assertOk()
        ->assertSee('class="msw-task"', false)
        ->assertDontSee('data-field="business.', false)
        ->assertDontSee(route('admin.dashboard.index'))
        ->assertDontSee(route('admin.seller_workspace.income'));

    get(route('admin.dashboard.index'))->assertUnauthorized();
    getJson(route('admin.dashboard.stats', ['type' => 'over-all']))->assertUnauthorized();
});

it('uses native list links when a dashboard role lacks the workspace list permissions', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard', 'sales', 'sales.orders', 'catalog', 'catalog.products']]);
    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    get(route('admin.dashboard.index'))->assertOk()
        ->assertSee(route('admin.sales.orders.index', ['workflow' => 'packing']))
        ->assertSee(route('admin.catalog.products.index', ['stock' => 'inactive']))
        ->assertDontSee(route('admin.seller_workspace.orders'))
        ->assertDontSee(route('admin.seller_workspace.products'));
});

it('validates dashboard filters before constructing reporting helpers', function () {
    $this->loginAsAdmin();

    foreach ([['start' => 'garbage'], ['start' => '2026-11-02', 'end' => '2026-11-01'], ['channel' => 'no-such-channel']] as $filters) {
        getJson(route('admin.dashboard.index', $filters))->assertUnprocessable();
    }
});

it('keeps the dashboard statistics endpoint available after combining the pages', function () {
    $this->loginAsAdmin();

    getJson(route('admin.dashboard.stats', ['type' => 'over-all']))->assertOk()
        ->assertJsonStructure(['statistics' => ['total_sales', 'total_orders', 'total_customers', 'avg_sales'], 'date_range']);
    getJson(route('admin.dashboard.stats', ['type' => 'unknown']))->assertNotFound();
});

it('matches the existing statistics endpoint for the selected range and channel', function () {
    $this->loginAsAdmin();

    $channel = core()->getDefaultChannel();
    $filters = ['start' => now()->subDays(2)->toDateString(), 'end' => now()->toDateString(), 'channel' => $channel->code];
    $before = getJson(route('admin.dashboard.stats', $filters + ['type' => 'over-all']))->assertOk()->json('statistics');

    Order::factory()->create(['channel_id' => $channel->id, 'status' => 'processing', 'base_grand_total_invoiced' => 120, 'base_grand_total_refunded' => 0, 'created_at' => now()->subDay()]);
    Order::factory()->create(['channel_id' => $channel->id, 'status' => 'processing', 'base_grand_total_invoiced' => 999, 'base_grand_total_refunded' => 0, 'created_at' => now()->subDays(10)]);

    $business = get(route('admin.dashboard.index', $filters))->assertOk()->viewData('business');
    $statistics = getJson(route('admin.dashboard.stats', $filters + ['type' => 'over-all']))->assertOk()->json('statistics');

    expect($business['range']['total_sales']['current'])->toEqual($before['total_sales']['current'] + 120)
        ->and($business['range']['total_orders']['current'])->toBe($before['total_orders']['current'] + 1)
        ->and($business['range'])->toEqual($statistics);
});

it('shows one home entry in the admin menu for a full admin', function () {
    $this->loginAsAdmin();

    $html = view('manpleasure-admin::admin-menu')->render();

    expect($html)->toContain(route('admin.dashboard.index'))
        ->and($html)->not->toContain('href="'.route('admin.seller_workspace.overview').'"');
});

it('keeps the workspace home entry in the admin menu for an admin without dashboard permission', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['sales', 'sales.workspace']]);
    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    $html = view('manpleasure-admin::admin-menu')->render();

    expect($html)->toContain(route('admin.seller_workspace.overview'))
        ->and($html)->not->toContain(route('admin.dashboard.index'));
});

it('requires login at every dashboard and workspace home address', function () {
    foreach ([route('admin.dashboard.index'), route('admin.seller_workspace.overview'), url(config('app.admin_url').'/workspace')] as $url) {
        get($url)->assertRedirect(route('admin.session.create'));
    }
});
