<?php

use Webkul\Product\Models\Product as ProductModel;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

it('should show the chooser button and a cell for each optional column on the product list', function () {
    // Act.
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))
        ->assertOk()
        ->assertSee('data-action="choose-columns"', false)
        ->assertSee('v-product-columns', false);

    $html = $response->getContent();

    // Assert.
    expect(strpos($html, '.mpc-panel {'))->toBeLessThan(strpos($html, '<body'));

    foreach (array_keys(config('manpleasure-product-columns')) as $key) {
        $response->assertSee('data-col="'.$key.'"', false);
    }
});

it('should return the cost, deal price and flags of a product to the product list', function () {
    // Arrange.
    $product = ProductModel::factory()->simple()->create();

    $this->loginAsAdmin();

    putJson(route('admin.catalog.product_pricing.update', $product->id), [
        'price' => '39.90',
        'special_price' => '34.90',
        'cost' => '18.00',
    ])->assertOk();

    // Act.
    $records = getJson(route('admin.catalog.products.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->json('records');

    $record = collect($records)->firstWhere('product_id', $product->id);

    // Assert.
    expect($record)->not->toBeNull()
        ->and((float) $record['cost'])->toBe(18.0)
        ->and((float) $record['special_price'])->toBe(34.9)
        ->and($record)->toHaveKeys(['weight', 'created_at', 'updated_at', 'new', 'featured', 'attribute_family', 'images_count', 'short_description']);
});

it('should sort the product list by cost', function () {
    // Arrange.
    $cheap = ProductModel::factory()->simple()->create();

    $dear = ProductModel::factory()->simple()->create();

    $this->loginAsAdmin();

    putJson(route('admin.catalog.product_pricing.update', $cheap->id), ['price' => '39.90', 'cost' => '5.00'])->assertOk();

    putJson(route('admin.catalog.product_pricing.update', $dear->id), ['price' => '39.90', 'cost' => '25.00'])->assertOk();

    // Act.
    $ids = collect(
        getJson(route('admin.catalog.products.index', ['sort' => ['column' => 'cost', 'order' => 'desc']]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->json('records')
    )->pluck('product_id')->all();

    // Assert.
    expect(array_search($dear->id, $ids))->toBeLessThan(array_search($cheap->id, $ids));
});
