<?php

use App\Repositories\ProductStockRepository;
use Database\Seeders\ManPleasureAttributeSeeder;
use Database\Seeders\ManPleasureCategorySeeder;
use Database\Seeders\ManPleasureFamilySeeder;
use Illuminate\Support\Facades\DB;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Models\Product as ProductModel;
use Webkul\Product\Models\ProductFlat;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\get;

/**
 * A product with this name and status in the list.
 */
function lifecycleProduct(string $name, int $status = 1): ProductModel
{
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    ProductFlat::where('product_id', $product->id)->update(['name' => $name, 'status' => $status]);

    return $product;
}

/**
 * The page's fields for a lubricant.
 */
function lifecyclePayload(array $overrides = []): array
{
    app(ManPleasureAttributeSeeder::class)->run();
    app(ManPleasureCategorySeeder::class)->run();
    app(ManPleasureFamilySeeder::class)->run();

    return array_merge([
        'category_id' => app(CategoryRepository::class)->findBySlugOrFail('water-based-lubricants')->id,
        'mp_product_type' => (int) DB::table('attribute_options')
            ->join('attributes', 'attributes.id', '=', 'attribute_options.attribute_id')
            ->where('attributes.code', 'mp_product_type')
            ->where('attribute_options.admin_name', 'Lubricant')
            ->value('attribute_options.id'),
        'name' => 'Draft Lubricant',
        'sku' => 'MP-LUB-960',
        'url_key' => 'draft-lubricant-960',
        'price' => '19.90',
        'stock' => '5',
        'weight' => '0.14',
        'short_description' => 'A smooth, long-lasting water-based lubricant.',
        'description' => '<p>Compatible with latex condoms and silicone toys. Non-staining and easy to rinse.</p>',
        'status' => '0',
    ], $overrides);
}

it('shows the five tabs in order on the product list, with the old tabs gone', function () {
    $this->loginAsAdmin();

    expect(array_keys(ProductStockRepository::PRODUCT_TABS))->toBe(['active', 'inactive', 'draft', 'obsolete', 'all']);

    $html = get(route('admin.seller_workspace.products'))->assertOk()->getContent();

    preg_match('/<div class="msw-tabs">(.*?)<\/div>/s', $html, $nav);
    preg_match_all('/>([^<]+)<\/a>/', $nav[1] ?? '', $tabs);

    expect(array_map('trim', $tabs[1]))->toBe(['Online', 'Offline', 'Draft', 'Obsolete', 'All'])
        ->and($html)->not->toContain('Pending QC');
});

it('moves a product to Obsolete and off the store, and restores it to Offline', function () {
    $online = lifecycleProduct('Online item');
    $other = lifecycleProduct('Other item');

    $this->loginAsAdmin();

    $list = fn (string $tab) => get(route('admin.seller_workspace.products', ['tab' => $tab]))->assertOk();

    $list('active')->assertSee('Online item')->assertSee('Other item')->assertSee('Mark as Obsolete');
    $list('obsolete')->assertDontSee('Online item');

    $this->post(route('admin.seller_workspace.product_lifecycle', $online->id), ['state' => 'obsolete'])->assertRedirect();

    expect((bool) ProductModel::query()->findOrFail($online->id)->status)->toBeFalse();

    $list('obsolete')->assertSee(ProductModel::query()->findOrFail($online->id)->name)->assertSee('Remove from Obsolete')->assertDontSee('Other item');
    $list('active')->assertSee('Other item');
    $list('inactive')->assertDontSee(ProductModel::query()->findOrFail($online->id)->sku);

    $this->post(route('admin.seller_workspace.product_lifecycle', $online->id), ['state' => 'offline'])->assertRedirect();

    $list('obsolete')->assertDontSee(ProductModel::query()->findOrFail($online->id)->sku);
    $list('inactive')->assertSee(ProductModel::query()->findOrFail($online->id)->sku);

    expect((bool) ProductModel::query()->findOrFail($online->id)->status)->toBeFalse();

    $this->post(route('admin.seller_workspace.product_lifecycle', $other->id), ['state' => 'nonsense'])->assertRedirect();

    expect((bool) ProductModel::query()->findOrFail($other->id)->status)->toBeTrue();
});

it('refuses to change a product without edit permission', function () {
    $product = lifecycleProduct('Protected item');

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['catalog', 'catalog.products', 'catalog.products.workspace']]);

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    $this->post(route('admin.seller_workspace.product_lifecycle', $product->id), ['state' => 'obsolete'])->assertForbidden();

    get(route('admin.seller_workspace.products'))->assertOk()->assertDontSee('Mark as Obsolete');

    expect((bool) ProductModel::query()->findOrFail($product->id)->status)->toBeTrue();
});

it('lets a limited role with edit permission mark a product obsolete', function () {
    $product = lifecycleProduct('Allowed item');

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['catalog', 'catalog.products', 'catalog.products.edit', 'catalog.products.workspace']]);

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    get(route('admin.seller_workspace.products'))->assertOk()->assertSee('Mark as Obsolete');

    $this->post(route('admin.seller_workspace.product_lifecycle', $product->id), ['state' => 'obsolete'])->assertRedirect();

    expect((bool) ProductModel::query()->findOrFail($product->id)->status)->toBeFalse();
});

it('separates Draft from Offline when saving, and clears the state when a product goes online', function () {
    $this->loginAsAdmin();

    $payload = lifecyclePayload();

    $this->post(route('admin.catalog.product_entry.store'), $payload + ['draft' => 1], ['Accept' => 'application/json'])->assertOk();
    $this->post(route('admin.catalog.product_entry.store'), array_merge($payload, ['sku' => 'MP-LUB-961', 'url_key' => 'offline-lubricant-961', 'name' => 'Offline Lubricant']), ['Accept' => 'application/json'])->assertOk();

    get(route('admin.seller_workspace.products', ['tab' => 'draft']))->assertSee('MP-LUB-960')->assertDontSee('MP-LUB-961');
    get(route('admin.seller_workspace.products', ['tab' => 'inactive']))->assertSee('MP-LUB-961')->assertDontSee('MP-LUB-960');

    $draft = ProductModel::query()->where('sku', 'MP-LUB-960')->firstOrFail();

    $this->put(route('admin.catalog.product_entry.update', $draft->id), array_merge($payload, ['status' => '1']), ['Accept' => 'application/json'])->assertOk();

    get(route('admin.seller_workspace.products', ['tab' => 'draft']))->assertDontSee('MP-LUB-960');
    get(route('admin.seller_workspace.products', ['tab' => 'active']))->assertSee('MP-LUB-960');
});

it('keeps an obsolete product obsolete when it is edited and left off', function () {
    $this->loginAsAdmin();

    $payload = lifecyclePayload(['status' => '1']);

    $this->post(route('admin.catalog.product_entry.store'), $payload, ['Accept' => 'application/json'])->assertOk();

    $product = ProductModel::query()->where('sku', 'MP-LUB-960')->firstOrFail();

    $this->post(route('admin.seller_workspace.product_lifecycle', $product->id), ['state' => 'obsolete'])->assertRedirect();

    $this->put(route('admin.catalog.product_entry.update', $product->id), array_merge($payload, ['status' => '0', 'name' => 'Renamed']), ['Accept' => 'application/json'])->assertOk();

    get(route('admin.seller_workspace.products', ['tab' => 'obsolete']))->assertSee('MP-LUB-960');

    $this->put(route('admin.catalog.product_entry.update', $product->id), array_merge($payload, ['status' => '1']), ['Accept' => 'application/json'])->assertOk();

    get(route('admin.seller_workspace.products', ['tab' => 'obsolete']))->assertDontSee('MP-LUB-960');
    get(route('admin.seller_workspace.products', ['tab' => 'active']))->assertSee('MP-LUB-960');
});

it('puts a product online or offline from the switch in the Action column and clears its state', function () {
    $product = lifecycleProduct('Switch item');

    $this->loginAsAdmin();

    $this->post(route('admin.seller_workspace.product_lifecycle', $product->id), ['state' => 'obsolete'])->assertRedirect();

    $html = get(route('admin.seller_workspace.products', ['tab' => 'obsolete']))->assertOk()->getContent();

    expect($html)->toContain('name="state" value="online"')
        ->and($html)->toContain('role="switch" aria-checked="false" aria-label="Offline"')
        ->and($html)->toContain('<span class="msw-online">Offline</span>');

    $this->post(route('admin.seller_workspace.product_lifecycle', $product->id), ['state' => 'online'])->assertRedirect();

    expect((bool) ProductModel::query()->findOrFail($product->id)->status)->toBeTrue();

    $html = get(route('admin.seller_workspace.products', ['tab' => 'active']))->assertOk()->getContent();

    expect($html)->toContain('name="state" value="offline"')
        ->and($html)->toContain('role="switch" aria-checked="true" aria-label="Online"')
        ->and($html)->toContain('<span class="msw-online is-on">Online</span>')
        ->and($html)->toContain('<th>Status</th>');

    get(route('admin.seller_workspace.products', ['tab' => 'obsolete']))->assertDontSee($product->sku);

    $this->post(route('admin.seller_workspace.product_lifecycle', $product->id), ['state' => 'offline'])->assertRedirect();

    expect((bool) ProductModel::query()->findOrFail($product->id)->status)->toBeFalse();

    get(route('admin.seller_workspace.products', ['tab' => 'inactive']))->assertSee($product->sku);
});

it('lists Edit, Copy, Create Stock Alert, Mark as Obsolete and Delete in that order under the switch', function () {
    $product = lifecycleProduct('Menu item');

    $this->loginAsAdmin();

    $html = get(route('admin.seller_workspace.products', ['tab' => 'active', 'search' => 'Menu item']))->assertOk()->getContent();

    preg_match('/<div class="msw-action">(.*?)<\/td>/s', $html, $action);

    $cell = $action[1] ?? '';

    expect($cell)->toContain('role="switch"')
        ->and(strpos($cell, 'role="switch"'))->toBeLessThan(strpos($cell, 'data-msw-more'));

    $menu = preg_replace('/\s+/', ' ', strip_tags(substr($cell, strpos($cell, 'msw-more-menu'))));
    $positions = array_map(fn (string $label) => strpos($menu, $label), ['Edit', 'Copy', 'Create Stock Alert', 'Mark as Obsolete', 'Delete']);

    expect($positions)->not->toContain(false)
        ->and($positions)->toBe(collect($positions)->sort()->values()->all())
        ->and($cell)->toContain(e(route('admin.seller_workspace.edit_product', $product->id)))
        ->and($cell)->toContain(e(route('admin.catalog.products.copy', $product->id)))
        ->and($cell)->toContain('data-msw-open="msw-alert-'.$product->id.'"')
        ->and($cell)->toContain(e(route('admin.catalog.products.delete', $product->id)));
});

it('shows only the menu entries a limited role may use, and a switch it cannot press', function () {
    lifecycleProduct('Viewer item');

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['catalog', 'catalog.products', 'catalog.products.workspace']]);

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    $html = get(route('admin.seller_workspace.products', ['tab' => 'active']))->assertOk()->getContent();

    preg_match('/<div class="msw-action">(.*?)<\/td>/s', $html, $action);

    $menu = preg_replace('/\s+/', ' ', strip_tags($action[1] ?? ''));

    expect($menu)->toContain('Edit')
        ->and($menu)->not->toContain('Copy')
        ->and($menu)->not->toContain('Mark as Obsolete')
        ->and($menu)->not->toContain('Delete')
        ->and($action[1])->toContain('role="img" aria-label="Online"')
        ->and($action[1])->not->toContain('role="switch"');
});

it('sends the old Seller Center addresses to the matching workspace pages', function () {
    $product = lifecycleProduct('Moved item');

    $this->loginAsAdmin();

    get('/'.config('app.admin_url').'/seller-center')->assertRedirect('/'.config('app.admin_url').'/seller-workspace');
    get('/'.config('app.admin_url').'/seller-center/products')->assertRedirect('/'.config('app.admin_url').'/seller-workspace/products');
    get('/'.config('app.admin_url').'/seller-center/products/add')->assertRedirect('/'.config('app.admin_url').'/seller-workspace/products/add');
    get('/'.config('app.admin_url').'/seller-center/products/'.$product->id.'/edit')->assertRedirect('/'.config('app.admin_url').'/seller-workspace/products/'.$product->id.'/edit');
    expect($this->post('/'.config('app.admin_url').'/seller-center/products/'.$product->id.'/lifecycle', ['state' => 'obsolete'])->getStatusCode())->toBeIn([404, 405])
        ->and((bool) ProductModel::query()->findOrFail($product->id)->status)->toBeTrue();

    auth()->guard('admin')->logout();

    get('/'.config('app.admin_url').'/seller-center')->assertRedirect(route('admin.session.create'));

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']]);

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    get('/'.config('app.admin_url').'/seller-center')->assertUnauthorized();
    get('/'.config('app.admin_url').'/seller-center/products')->assertUnauthorized();
});
