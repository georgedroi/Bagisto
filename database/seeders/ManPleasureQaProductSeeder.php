<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

class ManPleasureQaProductSeeder extends Seeder
{
    public const SIMPLE_SKU = 'MP-QA-SIMPLE-001';
    public const CONFIGURABLE_SKU = 'MP-QA-CONFIG-001';

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->cleanup();

            $family = AttributeFamily::where('code', 'manpleasure_core')->firstOrFail();
            $channel = Channel::where('code', 'default')->firstOrFail();
            $source = InventorySource::where('code', 'default')->firstOrFail();
            $simpleCategory = $this->category('manual-strokers');
            $configurableCategory = $this->category('rings-enhancers');

            $simple = $this->createSimple(
                $family->id,
                $channel->id,
                $source->id,
                $simpleCategory->id
            );

            $configurable = $this->createConfigurable(
                $family->id,
                $channel->id,
                $source->id,
                $configurableCategory->id
            );

            $this->command?->info('QA simple product: '.$simple->sku.' (#'.$simple->id.')');
            $this->command?->info('QA configurable product: '.$configurable->sku.' (#'.$configurable->id.')');
            $this->command?->info('QA variants: '.$configurable->variants()->count());
        });
    }

    public function cleanup(): void
    {
        Product::whereIn('sku', [self::SIMPLE_SKU, self::CONFIGURABLE_SKU])
            ->orWhere('sku', 'like', self::CONFIGURABLE_SKU.'-%')
            ->get()
            ->each(fn (Product $product) => app(ProductRepository::class)->delete($product->id));
    }

    private function createSimple(int $familyId, int $channelId, int $sourceId, int $categoryId): Product
    {
        $repository = app(ProductRepository::class);
        $product = $repository->create([
            'type' => 'simple',
            'attribute_family_id' => $familyId,
            'sku' => self::SIMPLE_SKU,
        ]);

        return $this->saveProduct($product, $this->commonData(
            'MP QA Simple Device',
            'mp-qa-simple-device',
            self::SIMPLE_SKU,
            $channelId,
            $sourceId,
            $categoryId,
            12
        ) + [
            'mp_product_type' => $this->optionId('mp_product_type', 'Device'),
            'mp_material' => $this->optionId('mp_material', 'Silicone'),
            'mp_power_source' => $this->optionId('mp_power_source', 'Rechargeable'),
            'mp_rechargeable' => 1,
            'mp_body_safe' => 1,
        ], $product);
    }

    private function createConfigurable(int $familyId, int $channelId, int $sourceId, int $categoryId): Product
    {
        $repository = app(ProductRepository::class);
        $colorOptions = AttributeOption::whereHas('attribute', fn ($query) => $query->where('code', 'mp_variant_color'))
            ->orderBy('sort_order')->pluck('id')->all();
        $firmnessOptions = AttributeOption::whereHas('attribute', fn ($query) => $query->where('code', 'mp_firmness'))
            ->orderBy('sort_order')->pluck('id')->all();
        $colorAttribute = DB::table('attributes')->where('code', 'mp_variant_color')->first();
        $firmnessAttribute = DB::table('attributes')->where('code', 'mp_firmness')->first();

        $product = $repository->create([
            'type' => 'configurable',
            'attribute_family_id' => $familyId,
            'sku' => self::CONFIGURABLE_SKU,
        ]);
        $product->super_attributes()->sync([$colorAttribute->id, $firmnessAttribute->id]);

        foreach (array_slice($colorOptions, 0, 2) as $color) {
            foreach (array_slice($firmnessOptions, 0, 2) as $firmness) {
                $variant = $repository->create([
                    'type' => 'simple',
                    'attribute_family_id' => $familyId,
                    'sku' => self::CONFIGURABLE_SKU.'-'.$color.'-'.$firmness,
                    'parent_id' => $product->id,
                ]);
                $this->saveProduct($variant, $this->commonData(
                    $variant->sku,
                    strtolower($variant->sku),
                    $variant->sku,
                    $channelId,
                    $sourceId,
                    $categoryId,
                    5
                ) + [
                    'mp_variant_color' => $color,
                    'mp_firmness' => $firmness,
                ]);
            }
        }

        $this->saveProduct($product, $this->commonData(
            'MP QA Configurable Ring',
            'mp-qa-configurable-ring',
            self::CONFIGURABLE_SKU,
            $channelId,
            $sourceId,
            $categoryId,
            0
        ) + [
            'mp_product_type' => $this->optionId('mp_product_type', 'Device'),
            'mp_material' => $this->optionId('mp_material', 'TPE'),
            'mp_body_safe' => 1,
        ], $product);

        return $product->fresh();
    }

    private function saveProduct(Product $product, array $data): Product
    {
        $attributeRepository = app(\Webkul\Attribute\Repositories\AttributeRepository::class);
        $attributeValueRepository = app(\Webkul\Product\Repositories\ProductAttributeValueRepository::class);
        $inventoryRepository = app(\Webkul\Product\Repositories\ProductInventoryRepository::class);
        $codes = [
            'sku', 'name', 'url_key', 'short_description', 'description', 'price',
            'weight', 'status', 'visible_individually', 'guest_checkout', 'manage_stock',
            'mp_product_type', 'mp_material', 'mp_power_source', 'mp_rechargeable',
            'mp_body_safe', 'mp_variant_color', 'mp_firmness',
        ];
        $attributes = $attributeRepository->findWhereIn('code', $codes);

        $attributeValueRepository->saveValues($data, $product, $attributes);
        $product->channels()->sync($data['channels']);
        $product->categories()->sync($data['categories']);
        $inventoryRepository->saveInventories($data, $product);

        return $product->fresh();
    }

    private function commonData(string $name, string $urlKey, string $sku, int $channelId, int $sourceId, int $categoryId, int $qty): array
    {
        return [
            'name' => $name,
            'url_key' => $urlKey,
            'short_description' => 'Controlled ManPleasure QA fixture.',
            'description' => 'Controlled QA fixture for native catalog verification.',
            'price' => 99.00,
            'weight' => 0.25,
            'status' => 1,
            'visible_individually' => 1,
            'guest_checkout' => 1,
            'manage_stock' => 1,
            'channels' => [$channelId],
            'categories' => [$categoryId],
            'inventories' => [$sourceId => $qty],
            'channel' => 'default',
            'locale' => 'en',
            'sku' => $sku,
        ];
    }

    private function optionId(string $attributeCode, string $label): int
    {
        return AttributeOption::whereHas('attribute', fn ($query) => $query->where('code', $attributeCode))
            ->where('admin_name', $label)
            ->value('id');
    }

    private function category(string $slug): Category
    {
        return Category::whereHas('translations', fn ($query) => $query->where('locale', 'en')->where('slug', $slug))
            ->firstOrFail();
    }
}
