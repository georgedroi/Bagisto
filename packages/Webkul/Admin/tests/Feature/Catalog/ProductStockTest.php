<?php

use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Product\Models\ProductFlat;
use Webkul\Product\Models\ProductInventory;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

it('should give the stock of a simple product for each active warehouse', function () {
    $product = (new ProductFaker)->getSimpleProductFactory()->create();
    $source = InventorySource::query()->where('status', 1)->firstOrFail();

    ProductInventory::query()->where('product_id', $product->id)->delete();
    ProductInventory::query()->create(['product_id' => $product->id, 'inventory_source_id' => $source->id, 'vendor_id' => 0, 'qty' => 7]);

    $this->loginAsAdmin();

    getJson(route('admin.catalog.product_stock.show', $product->id))
        ->assertOk()
        ->assertJsonPath('data.sku', $product->sku)
        ->assertJsonPath('data.total', 7)
        ->assertJsonFragment(['id' => $source->id, 'qty' => 7]);
});

it('should save only the stock and update the quantity the product list shows', function () {
    $product = (new ProductFaker)->getSimpleProductFactory()->create();
    $source = InventorySource::query()->where('status', 1)->firstOrFail();
    $name = $product->name;
    $price = (float) $product->price;

    $this->loginAsAdmin();

    putJson(route('admin.catalog.product_stock.update', $product->id), ['inventories' => [$source->id => 42]])
        ->assertOk()
        ->assertJsonPath('data.total', (int) ProductInventory::query()->where('product_id', $product->id)->sum('qty'))
        ->assertJsonPath('message', trans('admin::product-pricing.stock-saved'));

    $product->refresh();

    expect((int) ProductInventory::query()->where(['product_id' => $product->id, 'inventory_source_id' => $source->id])->value('qty'))->toBe(42)
        ->and((int) ProductFlat::query()->where('product_id', $product->id)->value('quantity'))->toBe((int) ProductInventory::query()->where('product_id', $product->id)->sum('qty'))
        ->and($product->name)->toBe($name)
        ->and((float) $product->price)->toBe($price);
});

it('should refuse a negative, fractional or missing quantity and an unknown warehouse', function () {
    $product = (new ProductFaker)->getSimpleProductFactory()->create();
    $source = InventorySource::query()->where('status', 1)->firstOrFail();

    $this->loginAsAdmin();

    $url = route('admin.catalog.product_stock.update', $product->id);

    putJson($url, ['inventories' => [$source->id => -1]])->assertUnprocessable();
    putJson($url, ['inventories' => [$source->id => 1.5]])->assertUnprocessable();
    putJson($url, ['inventories' => [$source->id => null]])->assertUnprocessable();
    putJson($url, ['inventories' => []])->assertUnprocessable();
    putJson($url, ['inventories' => [999999 => 5]])->assertUnprocessable();

    expect(ProductInventory::query()->where(['product_id' => $product->id, 'inventory_source_id' => 999999])->exists())->toBeFalse();
});

it('should refuse to edit the stock of a product that holds none of its own', function () {
    $product = (new ProductFaker)->getConfigurableProductFactory()->create();
    $source = InventorySource::query()->where('status', 1)->firstOrFail();

    $this->loginAsAdmin();

    getJson(route('admin.catalog.product_stock.show', $product->id))
        ->assertUnprocessable()
        ->assertJsonPath('message', trans('admin::product-pricing.stock-not-simple'));

    putJson(route('admin.catalog.product_stock.update', $product->id), ['inventories' => [$source->id => 5]])
        ->assertUnprocessable();
});

it('should refuse stock changes to a role without the permission', function () {
    $product = (new ProductFaker)->getSimpleProductFactory()->create();
    $source = InventorySource::query()->where('status', 1)->firstOrFail();

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['catalog', 'catalog.products', 'catalog.products.workspace']]);

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    $before = (int) ProductInventory::query()->where('product_id', $product->id)->sum('qty');

    putJson(route('admin.catalog.product_stock.update', $product->id), ['inventories' => [$source->id => $before + 5]])
        ->assertUnauthorized();

    expect((int) ProductInventory::query()->where('product_id', $product->id)->sum('qty'))->toBe($before);
});
