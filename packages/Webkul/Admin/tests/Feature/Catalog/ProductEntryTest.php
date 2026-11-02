<?php

use App\Support\ProductEntryCatalog;
use Database\Seeders\ManPleasureAttributeSeeder;
use Database\Seeders\ManPleasureCategorySeeder;
use Database\Seeders\ManPleasureFamilySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Models\Product as ProductModel;
use Webkul\Product\Models\ProductFlat;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

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

it('should save a product that leaves the fields of its family empty', function () {
    // Arrange.
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    // Act and Assert.
    submitEntry(entryPayload([
        'category_id' => entryCategoryId('thin-condoms'),
        'mp_product_type' => entryOptionId('mp_product_type', 'Condom'),
        'sku' => 'MP-CON-901',
        'url_key' => 'thin-condoms-10-pack',
    ]))->assertOk();

    $product = ProductModel::query()->where('sku', 'MP-CON-901')->firstOrFail();

    expect($product->attribute_family->code)->toBe('manpleasure_condoms')
        ->and($product->mp_width_mm)->toBeNull();

    $this->put(route('admin.catalog.product_entry.update', $product->id), entryPayload([
        'category_id' => entryCategoryId('thin-condoms'),
        'mp_product_type' => entryOptionId('mp_product_type', 'Condom'),
        'sku' => 'MP-CON-901',
        'url_key' => 'thin-condoms-10-pack',
        'name' => 'Thin Condoms',
    ]), ['Accept' => 'application/json'])->assertOk();

    expect(ProductModel::query()->findOrFail($product->id)->name)->toBe('Thin Condoms');
});

it('should still refuse a family field with an invalid value', function () {
    // Arrange.
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    // Act and Assert.
    submitEntry(entryPayload([
        'category_id' => entryCategoryId('thin-condoms'),
        'mp_product_type' => entryOptionId('mp_product_type', 'Condom'),
        'sku' => 'MP-CON-902',
        'url_key' => 'thin-condoms-20-pack',
        'mp_width_mm' => 'wide',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['mp_width_mm']);
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

it('saves the promotion image as the first product image', function () {
    seedProductEntryCatalog();

    Storage::fake();

    $this->loginAsAdmin();

    submitEntry(entryPayload(), ['images' => ['files' => [
        'image_promotion' => UploadedFile::fake()->image('promotion.png', 800, 800),
        'image_0' => UploadedFile::fake()->image('front.png', 800, 800),
    ]]])->assertOk();

    $images = ProductModel::query()->where('sku', 'MP-LUB-901')->firstOrFail()->images()->orderBy('position')->get();

    expect($images)->toHaveCount(2)
        ->and($images->first()->position)->toBeLessThan($images->last()->position);
});

it('opens a saved product on the Add Product page and keeps what the page does not show', function () {
    seedProductEntryCatalog();

    Storage::fake();

    $this->loginAsAdmin();

    $this->post(route('admin.catalog.product_entry.store'), entryPayload(['images' => ['files' => ['image_0' => UploadedFile::fake()->image('one.png', 800, 800)]]]))->assertOk();

    $product = ProductModel::query()->where('sku', 'MP-LUB-901')->firstOrFail();
    $image = $product->images()->firstOrFail();

    $product->up_sells()->attach((new ProductFaker)->getSimpleProductFactory()->create()->id);

    get(route('admin.seller_workspace.edit_product', $product->id))
        ->assertOk()
        ->assertSee('Edit Product')
        ->assertSee('MP-LUB-901', false);

    $this->put(route('admin.catalog.product_entry.update', $product->id), entryPayload([
        'name' => 'Renamed Lubricant',
        'price' => '19.90',
        'special_price' => '',
        'stock' => '7',
        'images' => ['files' => [$image->id => '']],
    ]), ['Accept' => 'application/json'])->assertOk();

    $fresh = ProductModel::query()->findOrFail($product->id);

    expect($fresh->name)->toBe('Renamed Lubricant')
        ->and((float) $fresh->price)->toBe(19.9)
        ->and($fresh->special_price)->toBeNull()
        ->and((int) $fresh->inventories()->sum('qty'))->toBe(7)
        ->and($fresh->images()->pluck('id')->all())->toBe([$image->id])
        ->and($fresh->up_sells()->count())->toBe(1)
        ->and($fresh->categories()->count())->toBeGreaterThan(0);
});

it('moves a product to a category of another family, with that family\'s fields', function () {
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    submitEntry(entryPayload())->assertOk();

    $product = ProductModel::query()->where('sku', 'MP-LUB-901')->firstOrFail();
    $catalog = app(ProductEntryCatalog::class);
    $main = collect($catalog->categories())->first(fn (array $main) => $main['family'] && $main['family'] !== $product->attribute_family->code && $main['children']);
    $sub = $main['children'][0];

    $this->put(route('admin.catalog.product_entry.update', $product->id), entryPayload(['category_id' => $sub['id'], 'name' => 'Moved Product']), ['Accept' => 'application/json'])
        ->assertOk();

    $fresh = ProductModel::query()->findOrFail($product->id);

    expect($fresh->attribute_family->code)->toBe($main['family'])
        ->and($fresh->name)->toBe('Moved Product')
        ->and($fresh->categories()->pluck('categories.id')->sort()->values()->all())->toBe(collect([$main['id'], $sub['id']])->sort()->values()->all())
        ->and(ProductFlat::query()->where('product_id', $product->id)->value('name'))->toBe('Moved Product');

    expect($catalog->productData($product->id)['category_id'])->toBe($sub['id']);
});

it('refuses an edit without permission', function () {
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    submitEntry(entryPayload())->assertOk();

    $product = ProductModel::query()->where('sku', 'MP-LUB-901')->firstOrFail();

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['catalog', 'catalog.product_entry']]);

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    $this->put(route('admin.catalog.product_entry.update', $product->id), entryPayload(), ['Accept' => 'application/json'])->assertForbidden();
    get(route('admin.seller_workspace.edit_product', $product->id))->assertForbidden();
});

it('sends other product types to the product editor', function () {
    $this->loginAsAdmin();

    $bundle = ProductModel::factory()->create(['type' => 'bundle']);

    expect(app(ProductEntryCatalog::class)->editUrl($bundle->id, 'bundle'))->toBe(route('admin.catalog.products.edit', $bundle->id));

    get(route('admin.seller_workspace.edit_product', $bundle->id))->assertRedirect(route('admin.catalog.products.edit', $bundle->id));
});

it('stores a description picture in the Media Bank and gives its site address', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $url = $this->post(route('admin.catalog.product_entry.description_image'), ['image' => UploadedFile::fake()->image('detail.jpg', 1200, 900)], ['Accept' => 'application/json'])
        ->assertCreated()
        ->json('url');

    $asset = DB::table('media_assets')->where('collection', 'product-description')->first();

    expect($url)->toStartWith('/storage/media-library/')
        ->and($url)->toEndWith($asset->path)
        ->and($asset->width)->toBe(1200);

    Storage::disk('public')->assertExists($asset->path);
});

it('refuses a description picture that is not a JPG, PNG or WEBP image', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    foreach ([
        UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
        UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>'),
        UploadedFile::fake()->createWithContent('fake.jpg', 'not really a picture'),
        UploadedFile::fake()->image('huge.png', 100, 100)->size(6000),
    ] as $file) {
        $this->post(route('admin.catalog.product_entry.description_image'), ['image' => $file], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    expect(DB::table('media_assets')->where('collection', 'product-description')->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('refuses a description picture from an admin who cannot add products', function () {
    Storage::fake('public');

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['catalog', 'catalog.products']]);

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    $response = $this->post(route('admin.catalog.product_entry.description_image'), ['image' => UploadedFile::fake()->image('detail.jpg', 400, 400)], ['Accept' => 'application/json']);

    expect($response->status())->toBeIn([401, 403])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('saves a description with pictures and drops scripts, handlers and pictures from other sites', function () {
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    submitEntry(entryPayload(['description' => '<div style="text-align: center;">Smooth and long lasting, easy to rinse off.</div>'
        .'<img src="/storage/media-library/2026/11/detail.jpg" alt="Lubricant" style="max-width: 100%; height: auto;" onerror="alert(1)">'
        .'<img src="https://tracker.example.com/pixel.jpg"><script>alert(2)</script><a href="javascript:alert(3)">link</a>']))->assertOk();

    $description = ProductModel::query()->where('sku', 'MP-LUB-901')->firstOrFail()->description;

    expect($description)->toContain('src="/storage/media-library/2026/11/detail.jpg"')
        ->and($description)->toContain('text-align:center;')
        ->and($description)->toContain('max-width:100%;')
        ->and($description)->not->toContain('onerror')
        ->and($description)->not->toContain('tracker.example.com')
        ->and($description)->not->toContain('<script')
        ->and($description)->not->toContain('javascript:');
});

it('saves a description made only of pictures', function () {
    seedProductEntryCatalog();

    $this->loginAsAdmin();

    submitEntry(entryPayload(['description' => '<img src="/storage/media-library/2026/11/9b2f0d8e-detail-one.jpg" alt="" style="max-width: 100%; height: auto;">']))->assertOk();

    expect(ProductModel::query()->where('sku', 'MP-LUB-901')->firstOrFail()->description)->toContain('9b2f0d8e-detail-one.jpg');
});
