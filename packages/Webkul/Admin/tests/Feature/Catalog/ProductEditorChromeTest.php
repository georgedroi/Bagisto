<?php

use Webkul\Product\Models\Product as ProductModel;

use function Pest\Laravel\get;

it('should show the product editor without the blueprint panel and with a bar that is not pinned to the window', function () {
    // Arrange.
    $product = ProductModel::factory()->simple()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertDontSee('Product Editor Blueprint', false)
        ->assertDontSee('data-editor-blueprint', false)
        ->assertSee('data-editor-actions', false)
        ->assertDontSee('fixed bottom-12', false)
        ->assertSeeText(trans('admin::product-editor.draft'))
        ->assertSeeText(trans('admin::product-editor.publish'));
});

it('should link the media bank from the catalog menu', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.catalog.product_entry.index'))
        ->assertOk()
        ->assertSee(route('admin.media.index'), false)
        ->assertSeeText(trans('admin::product-editor.media-bank'));
});
