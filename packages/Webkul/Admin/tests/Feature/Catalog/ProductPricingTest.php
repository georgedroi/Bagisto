<?php

use Webkul\Product\Models\Product as ProductModel;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

it('should give the pricing of a simple product with its storefront link', function () {
    // Arrange.
    $product = ProductModel::factory()->simple()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    getJson(route('admin.catalog.product_pricing.show', $product->id))
        ->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.sku', $product->sku)
        ->assertJsonPath('data.has_cost_field', true)
        ->assertJsonStructure(['data' => ['price', 'special_price', 'special_price_from', 'special_price_to', 'cost', 'stock', 'storefront_url']]);
});

it('should refuse to edit the pricing of a product whose price is not stored on it', function () {
    // Arrange.
    $product = ProductModel::factory()->create(['type' => 'configurable']);

    // Act and Assert.
    $this->loginAsAdmin();

    getJson(route('admin.catalog.product_pricing.show', $product->id))
        ->assertUnprocessable()
        ->assertJsonPath('message', trans('admin::product-pricing.not-simple'));

    putJson(route('admin.catalog.product_pricing.update', $product->id), ['price' => '10.00'])
        ->assertUnprocessable();
});

it('should save the price, a dated special price and the cost without touching other fields', function () {
    // Arrange.
    $product = ProductModel::factory()->simple()->create();

    $name = $product->name;

    $this->loginAsAdmin();

    // Act.
    putJson(route('admin.catalog.product_pricing.update', $product->id), [
        'price' => '39.90',
        'special_price' => '34.90',
        'special_price_from' => '2026-11-11',
        'special_price_to' => '2026-11-14',
        'cost' => '18.00',
    ])
        ->assertOk()
        ->assertJsonPath('message', trans('admin::product-pricing.saved'))
        ->assertJsonPath('data.price', 39.9)
        ->assertJsonPath('data.special_price', 34.9);

    // Assert.
    $product = ProductModel::query()->findOrFail($product->id);

    expect((float) $product->price)->toBe(39.9)
        ->and((float) $product->special_price)->toBe(34.9)
        ->and((float) $product->cost)->toBe(18.0)
        ->and(substr((string) $product->special_price_from, 0, 10))->toBe('2026-11-11')
        ->and(substr((string) $product->special_price_to, 0, 10))->toBe('2026-11-14')
        ->and($product->name)->toBe($name);
});

it('should clear the special price and its dates when the deal is switched off', function () {
    // Arrange.
    $product = ProductModel::factory()->simple()->create();

    $this->loginAsAdmin();

    putJson(route('admin.catalog.product_pricing.update', $product->id), [
        'price' => '39.90',
        'special_price' => '34.90',
        'special_price_from' => '2026-11-11',
        'special_price_to' => '2026-11-14',
    ])->assertOk();

    // Act.
    putJson(route('admin.catalog.product_pricing.update', $product->id), [
        'price' => '39.90',
        'special_price' => null,
        'special_price_from' => null,
        'special_price_to' => null,
    ])->assertOk();

    // Assert.
    $product = ProductModel::query()->findOrFail($product->id);

    expect($product->special_price)->toBeNull()
        ->and($product->special_price_from)->toBeNull()
        ->and($product->special_price_to)->toBeNull()
        ->and((float) $product->price)->toBe(39.9);
});

it('should refuse a special price that is not lower than the price and an end date before the start', function () {
    // Arrange.
    $product = ProductModel::factory()->simple()->create();

    $this->loginAsAdmin();

    // Act and Assert.
    putJson(route('admin.catalog.product_pricing.update', $product->id), [
        'price' => '39.90',
        'special_price' => '39.90',
        'special_price_from' => '2026-11-14',
        'special_price_to' => '2026-11-11',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['special_price', 'special_price_to']);
});

it('should show the pricing window and the storefront links on the product list', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.catalog.products.index'))
        ->assertOk()
        ->assertSee('v-product-pricing', false)
        ->assertSeeText(trans('admin::product-pricing.modal-title'))
        ->assertSee('data-action="edit-pricing"', false)
        ->assertSee('data-action="view-storefront"', false);
});
