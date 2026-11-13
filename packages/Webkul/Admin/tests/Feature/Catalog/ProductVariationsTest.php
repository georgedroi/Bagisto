<?php

use App\Support\ProductEntryCatalog;
use Database\Seeders\ManPleasureAttributeSeeder;
use Database\Seeders\ManPleasureCategorySeeder;
use Database\Seeders\ManPleasureFamilySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Helpers\ConfigurableOption;
use Webkul\Product\Models\Product as ProductModel;
use Webkul\Product\Models\ProductFlat;

/**
 * Seed the categories, fields and families the page works from, and log in.
 */
function variationSetup(): void
{
    app(ManPleasureAttributeSeeder::class)->run();

    app(ManPleasureCategorySeeder::class)->run();

    app(ManPleasureFamilySeeder::class)->run();

    test()->loginAsAdmin();
}

/**
 * The page's fields for a condom product, without price and stock.
 */
function variationPayload(array $variationData, array $overrides = []): array
{
    return array_merge([
        'category_id' => app(CategoryRepository::class)->findBySlugOrFail('thin-condoms')->id,
        'mp_product_type' => (int) DB::table('attribute_options')
            ->join('attributes', 'attributes.id', '=', 'attribute_options.attribute_id')
            ->where('attributes.code', 'mp_product_type')
            ->where('attribute_options.admin_name', 'Condom')
            ->value('attribute_options.id'),
        'name' => 'Thin Condoms',
        'sku' => 'MP-CON-950',
        'url_key' => 'thin-condoms-950',
        'weight' => '0.14',
        'short_description' => 'A smooth, comfortable everyday condom.',
        'description' => '<p>Thin, comfortable and tested. A pack that fits an everyday routine with no fuss.</p>',
        'status' => '1',
        'variation_data' => json_encode($variationData),
    ], $overrides);
}

/**
 * Two variations, Color (Black, White) and Size (S, M), with a row for every combination.
 */
function colorAndSize(array $rowOverrides = []): array
{
    $rows = [];

    foreach ([0, 1] as $color) {
        foreach ([0, 1] as $size) {
            $rows[] = array_merge(['options' => [$color, $size], 'price' => '20.00', 'stock' => '10', 'sku' => ''], $rowOverrides[$color.$size] ?? []);
        }
    }

    return [
        'variations' => [
            ['name' => 'Color', 'options' => [['label' => 'Black', 'note' => 'Matte'], ['label' => 'White', 'note' => '']]],
            ['name' => 'Size', 'options' => [['label' => 'S', 'note' => 'Small'], ['label' => 'M', 'note' => '']]],
        ],
        'rows' => $rows,
    ];
}

function saveVariations(array $payload, array $files = []): TestResponse
{
    return test()->post(route('admin.catalog.product_entry.store'), array_merge($payload, $files), ['Accept' => 'application/json']);
}

function updateVariations(int $id, array $payload): TestResponse
{
    return test()->put(route('admin.catalog.product_entry.update', $id), $payload, ['Accept' => 'application/json']);
}

function savedProduct(): ProductModel
{
    return ProductModel::query()->where('sku', 'MP-CON-950')->firstOrFail();
}

it('creates a configurable product with one sellable variant per combination', function () {
    variationSetup();

    saveVariations(variationPayload(colorAndSize(['01' => ['price' => '25.50', 'stock' => '7', 'sku' => 'mine-1'], '10' => ['stock' => '0']])))->assertOk();

    $product = savedProduct();

    expect($product->type)->toBe('configurable')
        ->and($product->super_attributes->pluck('code')->sort()->values()->all())->toBe(['color', 'size'])
        ->and($product->variants)->toHaveCount(4);

    $own = ProductModel::query()->where('sku', 'mine-1')->firstOrFail();

    expect((float) $own->price)->toBe(25.5)
        ->and($own->parent_id)->toBe($product->id)
        ->and($own->name)->toBe('Thin Condoms Black M')
        ->and((int) $own->inventories()->sum('qty'))->toBe(7)
        ->and((bool) $own->getTypeInstance()->isSaleable())->toBeTrue();

    $empty = ProductModel::query()->where('sku', 'MP-CON-950-white-s')->firstOrFail();

    expect((bool) $empty->getTypeInstance()->isSaleable())->toBeFalse()
        ->and((int) $empty->inventories()->sum('qty'))->toBe(0);

    expect(ProductFlat::query()->whereIn('product_id', $product->variants->pluck('id'))->count())->toBeGreaterThanOrEqual(4)
        ->and(ProductFlat::query()->where('product_id', $product->id)->value('name'))->toBe('Thin Condoms');

    $repository = core()->getSingletonInstance(AttributeRepository::class);

    (fn () => $this->attributes = [])->call($repository);

    $fresh = ProductModel::query()->findOrFail($product->id);

    $config = app(ConfigurableOption::class)->getConfigurationConfig($fresh);

    expect(collect($config['attributes'])->pluck('code')->sort()->values()->all())->toBe(['color', 'size'])
        ->and(collect($config['attributes'])->firstWhere('code', 'color')['options'])->not->toBeEmpty()
        ->and(collect(collect($config['attributes'])->firstWhere('code', 'size')['options'])->pluck('label')->all())->toContain('S', 'M');

    expect((bool) $fresh->getTypeInstance()->isSaleable())->toBeTrue();

    $color = collect($config['attributes'])->firstWhere('code', 'color');
    $size = collect($config['attributes'])->firstWhere('code', 'size');

    auth()->forgetGuards();

    $this->postJson(route('shop.api.checkout.cart.store'), [
        'product_id' => $fresh->id,
        'quantity' => 2,
        'selected_configurable_option' => $own->id,
        'super_attribute' => [
            $color['id'] => collect($color['options'])->firstWhere('label', 'Black')['id'],
            $size['id'] => collect($size['options'])->firstWhere('label', 'M')['id'],
        ],
    ])->assertOk()->assertJsonPath('data.items.0.quantity', 2);

    $this->postJson(route('shop.api.checkout.cart.store'), [
        'product_id' => $fresh->id,
        'quantity' => 99,
        'selected_configurable_option' => $own->id,
    ])->assertStatus(400)->assertJsonMissingPath('redirect_uri');
});

it('shows the options that have stock on the customer page', function () {
    variationSetup();

    saveVariations(variationPayload(colorAndSize(['10' => ['stock' => '0'], '11' => ['stock' => '0']])))->assertOk();

    (fn () => $this->attributes = [])->call(core()->getSingletonInstance(AttributeRepository::class));

    $html = $this->get(route('shop.product_or_category.index', 'thin-condoms-950'))->assertOk()->getContent();

    expect($html)->toContain('Thin Condoms')
        ->and($html)->toContain('"label":"Black"')
        ->and($html)->not->toContain('"label":"White"');
});

it('reuses store fields for known names, and creates one new field for a new name', function () {
    variationSetup();

    $attributes = Attribute::query()->count();

    $data = colorAndSize();
    $data['variations'][0]['name'] = 'colour';
    $data['variations'][1]['name'] = 'Flavour';

    saveVariations(variationPayload($data))->assertOk();

    expect(Attribute::query()->count())->toBe($attributes + 1)
        ->and(savedProduct()->super_attributes->pluck('code')->sort()->values()->all())->toBe(['color', 'mp_var_flavour']);

    $data['variations'][1]['name'] = 'FLAVOUR';

    saveVariations(variationPayload($data, ['sku' => 'MP-CON-951', 'url_key' => 'thin-condoms-951']))->assertOk();

    expect(Attribute::query()->count())->toBe($attributes + 1);

    $fields = app(ProductEntryCatalog::class)->families()['manpleasure_condoms']['fields'];

    expect(array_column($fields, 'code'))->not->toContain('mp_var_flavour', 'color');
});

it('keeps the variants of kept combinations when editing, and removes the others', function () {
    variationSetup();

    saveVariations(variationPayload(colorAndSize()))->assertOk();

    $product = savedProduct();

    $kept = ProductModel::query()->where('sku', 'MP-CON-950-black-s')->firstOrFail();

    $data = colorAndSize(['00' => ['price' => '33.00', 'stock' => '3', 'sku' => 'MP-CON-950-black-s']]);
    $data['variations'][1]['options'] = [['label' => 'S', 'note' => 'Tiny']];
    $data['rows'] = [
        ['options' => [0, 0], 'price' => '33.00', 'stock' => '3', 'sku' => 'MP-CON-950-black-s'],
        ['options' => [1, 0], 'price' => '20.00', 'stock' => '10', 'sku' => ''],
    ];

    updateVariations($product->id, variationPayload($data, ['name' => 'Thin Condoms New']))->assertOk();

    $fresh = ProductModel::query()->findOrFail($product->id);

    expect($fresh->variants)->toHaveCount(2)
        ->and($fresh->name)->toBe('Thin Condoms New')
        ->and($fresh->variants->pluck('id'))->toContain($kept->id);

    $kept = ProductModel::query()->findOrFail($kept->id);

    expect((float) $kept->price)->toBe(33.0)
        ->and((int) $kept->inventories()->sum('qty'))->toBe(3)
        ->and($kept->name)->toBe('Thin Condoms New Black S');

    $page = app(ProductEntryCatalog::class)->productData($product->id);

    expect($page['variation_data']['variations'][0]['name'])->toBe('Color')
        ->and(array_column($page['variation_data']['variations'][0]['options'], 'label'))->toBe(['Black', 'White'])
        ->and($page['variation_data']['variations'][0]['options'][0]['note'])->toBe('Matte')
        ->and($page['variation_data']['variations'][1]['options'][0]['note'])->toBe('Tiny')
        ->and($page['variation_data']['rows'])->toHaveCount(2)
        ->and(collect($page['variation_data']['rows'])->firstWhere('sku', 'MP-CON-950-black-s')['stock'])->toBe(3);
});

it('turns a single product into one with variations and back', function () {
    variationSetup();

    $single = array_diff_key(variationPayload(colorAndSize()), ['variation_data' => 1]) + ['price' => '19.90', 'stock' => '5', 'mp_width_mm' => '52'];

    saveVariations($single)->assertOk();

    $product = savedProduct();

    expect($product->type)->toBe('simple');

    updateVariations($product->id, variationPayload(colorAndSize()))->assertOk();

    $fresh = ProductModel::query()->findOrFail($product->id);

    expect($fresh->type)->toBe('configurable')
        ->and($fresh->variants)->toHaveCount(4)
        ->and($fresh->price)->toBeNull();

    expect((bool) $fresh->variants->first()->getTypeInstance()->isSaleable())->toBeTrue()
        ->and((bool) $fresh->getTypeInstance()->isSaleable())->toBeTrue();

    updateVariations($product->id, $single)->assertOk();

    $fresh = ProductModel::query()->findOrFail($product->id);

    expect($fresh->type)->toBe('simple')
        ->and($fresh->variants)->toHaveCount(0)
        ->and($fresh->super_attributes)->toHaveCount(0)
        ->and((float) $fresh->price)->toBe(19.9)
        ->and((int) $fresh->inventories()->sum('qty'))->toBe(5);
});

it('saves an option picture on all of that option\'s variants', function () {
    variationSetup();

    Storage::fake();

    saveVariations(variationPayload(colorAndSize()), ['variation_images' => [0 => UploadedFile::fake()->image('black.png', 300, 300)]])->assertOk();

    $black = ProductModel::query()->where('sku', 'MP-CON-950-black-m')->firstOrFail();
    $white = ProductModel::query()->where('sku', 'MP-CON-950-white-m')->firstOrFail();

    expect($black->images)->toHaveCount(1)
        ->and($white->images)->toHaveCount(0);

    $data = colorAndSize();
    $data['variations'][0]['options'][0]['keep_image'] = true;
    $data['variations'][1]['options'][] = ['label' => 'L', 'note' => ''];
    $data['rows'][] = ['options' => [0, 2], 'price' => '20.00', 'stock' => '1', 'sku' => ''];
    $data['rows'][] = ['options' => [1, 2], 'price' => '20.00', 'stock' => '1', 'sku' => ''];

    updateVariations(savedProduct()->id, variationPayload($data))->assertOk();

    $large = ProductModel::query()->where('sku', 'MP-CON-950-black-l')->firstOrFail();

    expect(ProductModel::query()->findOrFail($black->id)->images)->toHaveCount(1)
        ->and($large->images)->toHaveCount(1);

    Storage::assertExists($large->images->first()->path);

    expect(app(ProductEntryCatalog::class)->productData(savedProduct()->id)['variation_data']['variations'][0]['options'][0]['image'])->not->toBeNull();
});

it('finishes a SKU ending in a hyphen with the option names', function () {
    variationSetup();

    $data = colorAndSize();
    $data['rows'] = array_map(fn (array $row) => array_merge($row, ['sku' => 'prefix-']), $data['rows']);

    saveVariations(variationPayload($data))->assertOk();

    expect(savedProduct()->variants->pluck('sku')->sort()->values()->all())
        ->toBe(['prefix-black-m', 'prefix-black-s', 'prefix-white-m', 'prefix-white-s']);
});

it('refuses bad variations without saving anything', function (Closure $change, string $errorKey) {
    variationSetup();

    $data = colorAndSize();

    $change($data);

    $before = ProductModel::query()->count();

    saveVariations(variationPayload($data))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$errorKey]);

    expect(ProductModel::query()->count())->toBe($before);
})->with([
    'a third variation' => [function (array &$data) {
        $data['variations'][] = ['name' => 'Scent', 'options' => [['label' => 'Rose']]];
    }, 'variations'],
    'a space at the end of a name' => [function (array &$data) {
        $data['variations'][1]['name'] = 'Size ';
    }, 'variations.1.name'],
    'a space at the start of an option' => [function (array &$data) {
        $data['variations'][0]['options'][0]['label'] = ' Black';
    }, 'variations.0.options.0.label'],
    'the same name twice' => [function (array &$data) {
        $data['variations'][1]['name'] = 'color';
    }, 'variations.1.name'],
    'the same option twice' => [function (array &$data) {
        $data['variations'][0]['options'][1]['label'] = 'black';
    }, 'variations.0.options.1.label'],
    'a missing combination' => [function (array &$data) {
        array_pop($data['rows']);
    }, 'variation_rows'],
    'a missing price' => [function (array &$data) {
        $data['rows'][2]['price'] = '';
    }, 'variation_rows.2.price'],
    'the same SKU on two rows' => [function (array &$data) {
        $data['rows'][0]['sku'] = 'same-sku';
        $data['rows'][1]['sku'] = 'same-sku';
    }, 'variation_rows.0.sku'],
    'a special price not below the price' => [function (array &$data) {
        $data['rows'][1]['special_price'] = '20.00';
    }, 'variation_rows.1.special_price'],
    'special price dates that end before they start' => [function (array &$data) {
        $data['rows'][1] = array_merge($data['rows'][1], ['special_price' => '15.00', 'special_price_from' => '2026-11-04', 'special_price_to' => '2026-11-01']);
    }, 'variation_rows.1.special_price_to'],
    'a SKU another product uses' => [function (array &$data) {
        ProductModel::factory()->create(['sku' => 'taken-sku']);

        $data['rows'][0]['sku'] = 'taken-sku';
    }, 'variation_rows.0.sku'],
]);

it('saves each variation\'s own special price and dates, and clears them when removed', function () {
    variationSetup();

    saveVariations(variationPayload(colorAndSize([
        '00' => ['special_price' => '15.00', 'special_price_from' => '2026-11-01', 'special_price_to' => '2026-11-04'],
        '01' => ['special_price' => '12.50'],
    ])))->assertOk();

    $dated = ProductModel::query()->where('sku', 'MP-CON-950-black-s')->firstOrFail();
    $open = ProductModel::query()->where('sku', 'MP-CON-950-black-m')->firstOrFail();
    $plain = ProductModel::query()->where('sku', 'MP-CON-950-white-s')->firstOrFail();

    expect((float) $dated->special_price)->toBe(15.0)
        ->and(substr((string) $dated->special_price_from, 0, 10))->toBe('2026-11-01')
        ->and(substr((string) $dated->special_price_to, 0, 10))->toBe('2026-11-04')
        ->and((float) $open->special_price)->toBe(12.5)
        ->and($open->special_price_from)->toBeNull()
        ->and($plain->special_price)->toBeNull();

    $rows = collect(app(ProductEntryCatalog::class)->productData(savedProduct()->id)['variation_data']['rows'])->keyBy('sku');

    expect($rows['MP-CON-950-black-s']['special_price'])->toBe('15.00')
        ->and($rows['MP-CON-950-black-s']['special_price_from'])->toBe('2026-11-01')
        ->and($rows['MP-CON-950-black-s']['special_price_to'])->toBe('2026-11-04')
        ->and($rows['MP-CON-950-black-m']['special_price'])->toBe('12.50')
        ->and($rows['MP-CON-950-white-s']['special_price'])->toBe('');

    updateVariations(savedProduct()->id, variationPayload(colorAndSize([
        '00' => ['sku' => 'MP-CON-950-black-s', 'special_price' => '', 'special_price_from' => '2026-11-01', 'special_price_to' => '2026-11-04'],
        '01' => ['sku' => 'MP-CON-950-black-m'],
        '10' => ['sku' => 'MP-CON-950-white-s'],
        '11' => ['sku' => 'MP-CON-950-white-m'],
    ])))->assertOk();

    $dated = ProductModel::query()->findOrFail($dated->id);

    expect($dated->special_price)->toBeNull()
        ->and($dated->special_price_from)->toBeNull()
        ->and($dated->special_price_to)->toBeNull()
        ->and(ProductModel::query()->findOrFail($open->id)->special_price)->toBeNull();
});

it('still requires price and stock on a product without variations', function () {
    variationSetup();

    saveVariations(array_diff_key(variationPayload(colorAndSize()), ['variation_data' => 1]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['price', 'stock']);
});

it('opens a product with variations on the Add Product page', function () {
    variationSetup();

    saveVariations(variationPayload(colorAndSize()))->assertOk();

    $product = savedProduct();

    expect(app(ProductEntryCatalog::class)->editUrl($product->id, 'configurable'))->toContain(config('app.admin_url').'/products/'.$product->id.'/edit');

    $this->get(route('admin.seller_workspace.edit_product', $product->id))
        ->assertOk()
        ->assertSee('msw-var-enable', false)
        ->assertSeeText(trans('admin::seller-workspace.add.variation.enable'));
});
