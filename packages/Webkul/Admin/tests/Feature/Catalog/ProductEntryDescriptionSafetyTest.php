<?php

use App\Support\ProductDescriptionSanitizer;
use Database\Seeders\ManPleasureAttributeSeeder;
use Database\Seeders\ManPleasureCategorySeeder;
use Database\Seeders\ManPleasureFamilySeeder;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Repositories\ProductRepository;

beforeEach(function () {
    app(ManPleasureAttributeSeeder::class)->run();
    app(ManPleasureCategorySeeder::class)->run();
    app(ManPleasureFamilySeeder::class)->run();

    $this->loginAsAdmin();
});

/**
 * The Add Product payload for a simple product with the given descriptions.
 */
function descriptionSafetyPayload(string $sku, string $description, string $shortDescription): array
{
    return [
        'category_id' => app(CategoryRepository::class)->findBySlugOrFail('water-based-lubricants')->id,
        'mp_product_type' => app(AttributeRepository::class)->with('options')->findOneByField('code', 'mp_product_type')->options->firstWhere('admin_name', 'Lubricant')->id,
        'name' => 'Description safety '.$sku,
        'sku' => $sku,
        'url_key' => strtolower($sku),
        'price' => '20.00',
        'stock' => '3',
        'weight' => '0.1',
        'status' => '1',
        'short_description' => $shortDescription,
        'description' => $description,
    ];
}

it('removes Vue template expressions from a description entered through the Add Product form and keeps its formatting', function (string $description) {
    $payload = descriptionSafetyPayload('QA-DESC-SAFE', $description, 'A neutral short description for this check.');

    $this->postJson(route('admin.catalog.product_entry.store'), $payload)->assertOk();

    $stored = (string) app(ProductRepository::class)->findOneByField('sku', 'QA-DESC-SAFE')->description;

    expect($stored)->not->toContain('{{')
        ->not->toContain('7*191')
        ->not->toContain('alert')
        ->toContain('<strong>bold</strong>')
        ->toContain('Keep this sentence about gentle daily care for sensitive skin.');
})->with([
    'plain expression' => ['<p>Keep this sentence about gentle daily care for sensitive skin. {{ 7*191 }} <strong>bold</strong></p>'],
    'entity encoded' => ['<p>Keep this sentence about gentle daily care for sensitive skin. &#123;&#123; 7*191 &#125;&#125; <strong>bold</strong></p>'],
    'split across tags' => ['<p>Keep this sentence about gentle daily care for sensitive skin. <strong>bold</strong></p><p>{{ alert(1) /*</p><p>*/ 7*191 }}</p>'],
    'blade raw echo' => ['<p>Keep this sentence about gentle daily care for sensitive skin. {!! 7*191 !!} <strong>bold</strong></p>'],
]);

it('cleans the short description entered through the Add Product form', function () {
    $payload = descriptionSafetyPayload('QA-SHORT-SAFE', '<p>A neutral description that is long enough to pass the sixty character minimum.</p>', '<p>Short <em>gentle</em> text {{ 7*191 }}<img src=x onerror="alert(1)"><script>alert(2)</script></p>');

    $this->postJson(route('admin.catalog.product_entry.store'), $payload)->assertOk();

    $stored = (string) app(ProductRepository::class)->findOneByField('sku', 'QA-SHORT-SAFE')->short_description;

    expect($stored)->toContain('<em>gentle</em>')
        ->not->toContain('onerror')
        ->not->toContain('<script')
        ->not->toContain('{{');
});

it('never leaves a template expression pair however the delimiters are nested', function () {
    $clean = app(ProductDescriptionSanitizer::class)->clean('<p>{{{{ 7*191 }}}} {{ {{ 7*191 }} }} {&#123; 7*191 }&#125; ok</p>');

    expect(preg_match('/\{\{.*\}\}/s', $clean))->toBe(0)
        ->and($clean)->toContain('ok');
});
