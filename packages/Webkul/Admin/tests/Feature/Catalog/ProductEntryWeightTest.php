<?php

use App\Support\ProductEntryCatalog;
use Database\Seeders\ManPleasureAttributeSeeder;
use Database\Seeders\ManPleasureCategorySeeder;
use Database\Seeders\ManPleasureFamilySeeder;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Repositories\ProductRepository;

it('preserves the shipping weight when a product is reopened and saved', function (string $weight, string $display) {
    app(ManPleasureAttributeSeeder::class)->run();
    app(ManPleasureCategorySeeder::class)->run();
    app(ManPleasureFamilySeeder::class)->run();

    $this->loginAsAdmin();

    $type = app(AttributeRepository::class)->with('options')->findOneByField('code', 'mp_product_type');

    $payload = [
        'category_id' => app(CategoryRepository::class)->findBySlugOrFail('water-based-lubricants')->id,
        'mp_product_type' => $type->options->firstWhere('admin_name', 'Lubricant')->id,
        'name' => 'Shipping weight rehearsal',
        'sku' => 'QA-WEIGHT-ROUNDTRIP',
        'url_key' => 'qa-weight-roundtrip',
        'price' => '20.00',
        'stock' => '3',
        'weight' => $weight,
        'short_description' => 'A neutral product for a shipping weight rehearsal.',
        'description' => '<p>A neutral product for verifying that reopening and saving its editor preserves the shipping weight.</p>',
        'status' => '1',
    ];

    $response = $this->postJson(route('admin.catalog.product_entry.store'), $payload)->assertOk();

    $id = (int) basename($response->json('data.redirect_url'));

    $data = app(ProductEntryCatalog::class)->productData($id);

    expect($data['weight'])->toBe($display);

    $this->putJson(route('admin.catalog.product_entry.update', $id), array_replace($payload, ['weight' => $data['weight']]))->assertOk();

    expect((float) app(ProductRepository::class)->findOrFail($id)->weight)->toBe((float) $weight);
})->with([
    'ten kilograms' => ['10', '10'],
    'twenty kilograms' => ['20', '20'],
    'one hundred kilograms' => ['100', '100'],
    'three hundred kilograms' => ['300', '300'],
    'one gram' => ['0.001', '0.001'],
    'fractional kilograms' => ['1.25', '1.25'],
    'decimal with a trailing zero' => ['10.50', '10.5'],
]);
