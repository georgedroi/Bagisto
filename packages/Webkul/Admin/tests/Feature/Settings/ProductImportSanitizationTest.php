<?php

use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Repositories\ImportBatchRepository;
use Webkul\DataTransfer\Repositories\ImportRepository;
use Webkul\Product\Repositories\ProductFlatRepository;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Import one product row the way a queued batch does and give the saved product.
 */
function importProductRow(array $row)
{
    $import = app(ImportRepository::class)->create([
        'type' => 'products',
        'action' => 'append',
        'validation_strategy' => 'stop-on-errors',
        'allowed_errors' => 0,
        'field_separator' => ',',
        'file_path' => 'qa-synthetic.csv',
        'process_in_queue' => 0,
        'image_source' => 'local',
    ]);

    $importer = app(Importer::class)->setImport($import)->setErrorHelper(app(Error::class));

    expect($importer->validateRow($row, 1))->toBeTrue();

    $importer->importBatch(app(ImportBatchRepository::class)->create(['import_id' => $import->id, 'data' => [$row]]));

    return app(ProductRepository::class)->findOneByField('sku', $row['sku']);
}

/**
 * A valid simple product row for the default family.
 */
function importableProductRow(array $values): array
{
    return $values + [
        'sku' => 'qa-import-clean',
        'url_key' => 'qa-import-clean',
        'type' => 'simple',
        'attribute_family_code' => 'default',
        'locale' => 'en',
        'channel' => 'default',
        'name' => 'QA imported item',
        'price' => '20',
        'weight' => '1',
        'status' => '1',
        'visible_individually' => '1',
        'guest_checkout' => '1',
    ];
}

it('holds imported rich text to the same allow-list as the product form', function () {
    $product = importProductRow(importableProductRow([
        'description' => '<p>Import sentinel <strong>retained</strong></p><img src="/missing" onerror="window.qaImport=1"><script>window.qaImport=2</script><a href="javascript:window.qaImport=3">bad link</a>'."<p>{{\n7*191\n}}</p>",
        'short_description' => '<p>Short sentinel</p><img src="/missing-short" onerror="window.qaImportShort=1">',
    ]));

    $flat = app(ProductFlatRepository::class)->findOneWhere(['sku' => 'qa-import-clean', 'locale' => 'en']);

    foreach ([$product->description, $flat->description] as $description) {
        expect($description)->toContain('Import sentinel <strong>retained</strong>')
            ->not->toContain('onerror')
            ->not->toContain('<script')
            ->not->toContain('javascript:')
            ->not->toContain('7*191');
    }

    foreach ([$product->short_description, $flat->short_description] as $short) {
        expect($short)->toContain('Short sentinel')
            ->not->toContain('onerror');
    }
});

it('keeps ordinary imported formatting, links, images and tables unchanged', function () {
    $description = '<h2 class="qa-heading">Care guide</h2>'
        .'<p style="text-align:center;">Read the <a href="https://example.com/care" title="Care">care notes</a> and <em>store</em> it dry.</p>'
        .'<ul><li>First point</li><li>Second point</li></ul>'
        .'<p><img src="/storage/qa/guide.png" alt="Guide" width="320" height="200"></p>'
        .'<table class="qa-table"><thead><tr><th>Size</th><th>Length</th></tr></thead><tbody><tr><td>M</td><td>12 cm</td></tr></tbody></table>';

    $product = importProductRow(importableProductRow(['description' => $description, 'short_description' => '<p>Plain <strong>summary</strong></p>']));

    expect($product->description)->toBe($description)
        ->and($product->short_description)->toBe('<p>Plain <strong>summary</strong></p>')
        ->and($product->name)->toBe('QA imported item');
});
