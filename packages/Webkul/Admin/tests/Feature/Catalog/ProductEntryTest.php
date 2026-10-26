<?php

use Database\Seeders\ManPleasureAttributeSeeder;
use Database\Seeders\ManPleasureCategorySeeder;
use Database\Seeders\ManPleasureFamilySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Models\Product as ProductModel;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

/**
 * Seed the categories, fields and families the Add product page works from.
 */
function seedProductEntryCatalog(): void
{
    app(ManPleasureAttributeSeeder::class)->run();

    app(ManPleasureCategorySeeder::class)->run();

    app(ManPleasureFamilySeeder::class)->run();
}

function entryCategoryId(string $slug): int
{
    return app(CategoryRepository::class)->findBySlugOrFail($slug)->id;
}

function entryOptionId(string $attributeCode, string $label): int
{
    return (int) DB::table('attribute_options')
        ->join('attributes', 'attributes.id', '=', 'attribute_options.attribute_id')
        ->where('attributes.code', $attributeCode)
        ->where('attribute_options.admin_name', $label)
        ->value('attribute_options.id');
}

/**
 * A complete, valid lubricant the page could submit.
 */
function entryPayload(array $overrides = []): array
{
    return array_merge([
        'category_id' => entryCategoryId('water-based-lubricants'),
        'mp_product_type' => entryOptionId('mp_product_type', 'Lubricant'),
        'name' => 'Water-Based Lubricant 100 ml',
        'sku' => 'MP-LUB-901',
        'url_key' => 'water-based-lubricant-100-ml',
        'price' => '39.90',
        'special_price' => '34.90',
        'cost' => '18.00',
        'stock' => '40',
        'weight' => '0.14',
        'short_description' => 'A smooth, long-lasting water-based lubricant.',
        'description' => '<p>Compatible with latex condoms and silicone toys. Non-staining and easy to rinse.</p>',
        'status' => '1',
        'new' => '1',
        'mp_volume_ml' => '100',
        'mp_lubricant_base' => entryOptionId('mp_lubricant_base', 'Water-Based'),
        'mp_condom_compatible' => '1',
    ], $overrides);
}

function submitEntry(array $payload, array $files = []): TestResponse
{
    return test()->post(route('admin.catalog.product_entry.store'), array_merge($payload, $files), ['Accept' => 'application/json']);
}

it('should show the add product page with the category tree and the family fields', function () {
    // Arrange.
    seedProductEntryCatalog();

    // Act and Assert.
    $this->loginAsAdmin();

    $html = get(route('admin.catalog.product_entry.index'))->assertOk()->getContent();

    expect(strpos($html, '.mpe-layout {'))->toBeLessThan(strpos($html, '<body'));

    get(route('admin.catalog.product_entry.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::product-entry.title'))
        ->assertSee('manpleasure_condoms', false)
        ->assertSee('Thin Condoms', false)
        ->assertSee('Width (mm)', false)
        ->assertSee('"has_cost":true', false)
        ->assertSeeText(trans('admin::product-entry.section-pricing'))
        ->assertSeeText(trans('admin::product-entry.section-audit'));
});

it('should suggest the next free SKU for a category', function () {
    // Arrange.
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    // Act and Assert.
    getJson(route('admin.catalog.product_entry.suggest_sku', ['category_id' => entryCategoryId('water-based-lubricants')]))
        ->assertOk()
        ->assertJsonPath('sku', 'MP-LUB-001');

    ProductModel::factory()->create(['sku' => 'MP-LUB-003']);

    getJson(route('admin.catalog.product_entry.suggest_sku', ['category_id' => entryCategoryId('water-based-lubricants')]))
        ->assertJsonPath('sku', 'MP-LUB-004');
});

it('should create a product in the family of its category with its fields, stock and picture', function () {
    // Arrange.
    seedProductEntryCatalog();

    Storage::fake();

    $this->loginAsAdmin();

    // Act.
    $response = submitEntry(entryPayload(), ['images' => ['files' => ['image_0' => UploadedFile::fake()->image('front.png', 800, 800)]]]);

    // Assert.
    $response->assertOk()->assertJsonStructure(['data' => ['redirect_url']]);

    $product = ProductModel::query()->where('sku', 'MP-LUB-901')->firstOrFail();

    expect($product->attribute_family->code)->toBe('manpleasure_lubricants')
        ->and((float) $product->price)->toBe(39.9)
        ->and((float) $product->special_price)->toBe(34.9)
        ->and((float) $product->cost)->toBe(18.0)
        ->and((bool) $product->status)->toBeTrue()
        ->and((bool) $product->new)->toBeTrue()
        ->and((string) $product->mp_volume_ml)->toBe('100')
        ->and($product->categories()->count())->toBe(2)
        ->and((int) $product->inventories()->sum('qty'))->toBe(40)
        ->and($product->images()->count())->toBe(1);
});

it('should save a draft that stays hidden from the storefront', function () {
    // Arrange.
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    // Act.
    submitEntry(entryPayload(['status' => '0']))->assertOk();

    // Assert.
    expect((bool) ProductModel::query()->where('sku', 'MP-LUB-901')->firstOrFail()->status)->toBeFalse();
});

it('should refuse a product that misses a required field of its family', function () {
    // Arrange.
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    $before = ProductModel::query()->count();

    // Act and Assert.
    submitEntry(entryPayload([
        'category_id' => entryCategoryId('thin-condoms'),
        'mp_product_type' => entryOptionId('mp_product_type', 'Condom'),
        'sku' => 'MP-CON-901',
        'url_key' => 'thin-condoms-10-pack',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['mp_width_mm', 'mp_pack_size']);

    expect(ProductModel::query()->count())->toBe($before);
});

it('should refuse a duplicate SKU and a special price that is not lower than the price', function () {
    // Arrange.
    seedProductEntryCatalog();

    ProductModel::factory()->create(['sku' => 'MP-LUB-901']);

    $this->loginAsAdmin();

    // Act and Assert.
    submitEntry(entryPayload(['special_price' => '39.90']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sku', 'special_price']);
});

it('should refuse a negative cost and a special price of zero', function () {
    // Arrange.
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    // Act and Assert.
    submitEntry(entryPayload(['cost' => '-1', 'special_price' => '0']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cost', 'special_price']);
});

it('should refuse a category that has no product family', function () {
    // Arrange.
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    // Act and Assert.
    submitEntry(entryPayload(['category_id' => entryCategoryId('lubricants-care')]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id']);
});

it('should ignore fields that belong to another family', function () {
    // Arrange.
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    // Act.
    submitEntry(entryPayload(['mp_width_mm' => '52']))->assertOk();

    // Assert.
    expect(ProductModel::query()->where('sku', 'MP-LUB-901')->firstOrFail()->mp_width_mm)->toBeNull();
});

it('should leave no product behind when saving fails halfway', function () {
    // Arrange.
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    Event::listen('catalog.product.update.before', fn () => throw new RuntimeException('forced failure'));

    // Act and Assert.
    submitEntry(entryPayload())->assertServerError();

    expect(ProductModel::query()->where('sku', 'MP-LUB-901')->exists())->toBeFalse();
});
