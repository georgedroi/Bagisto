<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Contracts\AttributeFamily;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Repositories\ProductRepository;

class ProductVariations
{
    /**
     * The family group that holds variation fields, kept apart so they stay out of Product Specification.
     */
    public const GROUP_CODE = 'mp_variations';

    /**
     * The prefix of a variation field created from a typed name.
     */
    protected const CODE_PREFIX = 'mp_var_';

    /**
     * Other spellings of a store field's name.
     */
    protected const ALIASES = ['colour' => 'color'];

    /**
     * The fields each variant saves.
     */
    protected const VARIANT_ATTRIBUTES = ['sku', 'name', 'url_key', 'short_description', 'description', 'price', 'special_price', 'special_price_from', 'special_price_to', 'weight', 'status', 'manage_stock'];

    /**
     * Create a new variations helper instance.
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
        protected ProductRepository $productRepository,
        protected ProductInventoryRepository $productInventoryRepository,
        protected ProductImageRepository $productImageRepository,
        protected ProductEntryCatalog $catalog
    ) {}

    /**
     * Each option combination in order, as lists of option positions.
     */
    public function combinations(array $variations): array
    {
        $combinations = [[]];

        foreach ($variations as $variation) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach (array_keys($variation['options'] ?? []) as $position) {
                    $next[] = [...$combination, $position];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * The SKU of each row: a blank one is made from the parent SKU, and one ending in a hyphen is finished with the option names.
     */
    public function skus(array $variations, array $rows, string $parentSku): array
    {
        return collect($rows)->map(function (array $row) use ($variations, $parentSku) {
            $sku = trim((string) ($row['sku'] ?? ''));

            if ($sku !== '' && ! str_ends_with($sku, '-')) {
                return $sku;
            }

            $suffix = collect($row['options'])
                ->map(fn ($position, $index) => Str::slug($variations[$index]['options'][$position]['label']) ?: 'option-'.$position)
                ->implode('-');

            return ($sku === '' ? $parentSku.'-' : $sku).$suffix;
        })->all();
    }

    /**
     * The SKUs already used by a product other than this one and its variants.
     */
    public function takenSkus(array $skus, ?int $productId): array
    {
        $query = $this->productRepository->whereIn('sku', $skus);

        if ($productId) {
            $own = $this->productRepository->findWhere(['parent_id' => $productId])->pluck('id')->push($productId)->all();

            $query = $query->whereNotIn('id', $own);
        }

        return $query->pluck('sku')->all();
    }

    /**
     * Save the variations on a configurable product: reuse the variant of a kept combination, create new ones and delete the rest.
     */
    public function sync(Product $product, AttributeFamily $family, array $data, array $values): void
    {
        $variations = $data['variations'];

        $images = $data['variation_images'] ?? [];

        $skus = $this->skus($variations, $data['variation_rows'], $product->sku);

        $resolved = [];

        foreach ($variations as $index => $variation) {
            $attribute = $this->attributeFor($variation['name']);

            $this->attachToFamily($attribute, $family);

            $resolved[$index] = [
                'attribute' => $attribute,
                'options' => array_map(fn (array $option) => $this->optionFor($attribute, $option['label'])->id, $variation['options']),
            ];
        }

        $attributes = collect($resolved)->pluck('attribute');

        $product->super_attributes()->sync($attributes->pluck('id')->all());

        $existing = $this->productRepository->findWhere(['parent_id' => $product->id])
            ->keyBy(fn ($variant) => $this->combinationKey($variant, $attributes->all()));

        $kept = [];

        $sourceId = $this->catalog->inventorySourceId();

        foreach ($data['variation_rows'] as $rowIndex => $row) {
            $optionIds = [];

            foreach ($row['options'] as $index => $position) {
                $optionIds[$resolved[$index]['attribute']->code] = $resolved[$index]['options'][$position];
            }

            $key = implode('|', $optionIds);

            $variant = $existing->get($key);

            $isNew = ! $variant;

            $variant = $variant ?? $this->productRepository->create([
                'type' => 'simple',
                'sku' => $skus[$rowIndex],
                'attribute_family_id' => $family->id,
                'parent_id' => $product->id,
            ]);

            $kept[] = $variant->id;

            $labels = collect($row['options'])->map(fn ($position, $index) => $variations[$index]['options'][$position]['label'])->implode(' ');

            $special = (string) ($row['special_price'] ?? '');

            $updated = $this->productRepository->update([
                ...$optionIds,
                'sku' => $skus[$rowIndex],
                'attribute_family_id' => $family->id,
                'name' => $values['name'].' '.$labels,
                'url_key' => Str::slug($skus[$rowIndex]),
                'short_description' => $values['short_description'],
                'description' => $values['description'],
                'price' => $row['price'],
                'special_price' => $special,
                'special_price_from' => $special === '' ? '' : (string) ($row['special_price_from'] ?? ''),
                'special_price_to' => $special === '' ? '' : (string) ($row['special_price_to'] ?? ''),
                'weight' => $values['weight'],
                'status' => 1,
                'manage_stock' => 1,
                'channel' => core()->getDefaultChannelCode(),
                'locale' => core()->getDefaultLocaleCodeFromDefaultChannel(),
            ], $variant->id, [...self::VARIANT_ATTRIBUTES, ...array_keys($optionIds)]);

            $this->productInventoryRepository->saveInventories(['inventories' => [$sourceId => (int) $row['stock']]], $updated);

            $updated->channels()->sync($product->channels->pluck('id')->all() ?: [core()->getDefaultChannel()->id]);

            $this->saveOptionImage($updated, $variations[0]['options'][$row['options'][0]], $images[$row['options'][0]] ?? null, $product, $resolved[0]['attribute']->id, $resolved[0]['options'][$row['options'][0]], $isNew);
        }

        foreach ($existing->filter(fn ($variant) => ! in_array($variant->id, $kept, true)) as $variant) {
            $this->productRepository->delete($variant->id);
        }

        $product->additional = array_merge($product->additional ?? [], [
            'variations' => collect($variations)->map(fn (array $variation, int $index) => [
                'attribute_id' => $resolved[$index]['attribute']->id,
                'name' => $variation['name'],
                'options' => collect($variation['options'])->map(fn (array $option, int $position) => [
                    'id' => $resolved[$index]['options'][$position],
                    'label' => $option['label'],
                    'note' => (string) ($option['note'] ?? ''),
                ])->all(),
            ])->all(),
        ]);

        $product->save();

        $product->load('variants');
    }

    /**
     * Remove a product's variations so it can be sold as a single product again.
     */
    public function clear(Product $product): void
    {
        foreach ($this->productRepository->findWhere(['parent_id' => $product->id]) as $variant) {
            $this->productRepository->delete($variant->id);
        }

        $product->super_attributes()->detach();

        $product->additional = collect($product->additional ?? [])->except('variations')->all();

        $product->save();

        $product->load('variants');
    }

    /**
     * A configurable product's variations for the Add Product page, in the order they were entered.
     */
    public function forPage(Product $product): array
    {
        $saved = collect($product->additional['variations'] ?? [])->keyBy('attribute_id');

        $variants = $product->variants;

        $variations = $product->super_attributes->map(function ($attribute) use ($saved, $variants) {
            $used = $variants->flatMap(fn ($variant) => $variant->attribute_values->where('attribute_id', $attribute->id)->pluck('integer_value'))->unique();

            $notes = collect($saved->get($attribute->id)['options'] ?? []);

            $order = $notes->pluck('id')->flip();

            $options = $attribute->options
                ->whereIn('id', $used->all())
                ->sortBy(fn ($option) => $order->get($option->id, PHP_INT_MAX))
                ->values();

            return [
                'attribute_id' => $attribute->id,
                'name' => $saved->get($attribute->id)['name'] ?? $attribute->admin_name,
                'options' => $options->map(function ($option) use ($notes, $variants, $attribute) {
                    $saved = $notes->firstWhere('id', $option->id);

                    $image = $variants
                        ->first(fn ($variant) => $variant->attribute_values->where('attribute_id', $attribute->id)->where('integer_value', $option->id)->isNotEmpty() && $variant->images->isNotEmpty())
                        ?->images->first();

                    return [
                        'id' => $option->id,
                        'label' => $saved['label'] ?? $option->admin_name,
                        'note' => $saved['note'] ?? '',
                        'image' => $image ? Storage::url($image->path) : null,
                    ];
                })->all(),
            ];
        })->values();

        $sourceId = $this->catalog->inventorySourceId();

        $rows = $variants->map(fn ($variant) => [
            'options' => $variations->map(function (array $variation) use ($variant) {
                $optionId = $variant->attribute_values->where('attribute_id', $variation['attribute_id'])->first()?->integer_value;

                return collect($variation['options'])->search(fn (array $option) => $option['id'] === $optionId);
            })->all(),
            'price' => $variant->price === null ? '' : number_format((float) $variant->price, 2, '.', ''),
            'special_price' => $variant->special_price === null ? '' : number_format((float) $variant->special_price, 2, '.', ''),
            'special_price_from' => substr((string) $variant->special_price_from, 0, 10),
            'special_price_to' => substr((string) $variant->special_price_to, 0, 10),
            'stock' => (int) $variant->inventories->where('inventory_source_id', $sourceId)->sum('qty'),
            'sku' => $variant->sku,
        ])->values()->all();

        return ['variations' => $variations->all(), 'rows' => $rows];
    }

    /**
     * The store field for a typed variation name, reusing an existing one and creating it when the name is new.
     */
    protected function attributeFor(string $name): Attribute
    {
        $wanted = Str::lower($name);

        $wanted = self::ALIASES[$wanted] ?? $wanted;

        $attributes = $this->attributeRepository->findWhere(['is_configurable' => 1, 'type' => 'select']);

        $found = $attributes->first(fn ($attribute) => Str::lower($attribute->admin_name) === $wanted || Str::lower($attribute->code) === $wanted);

        if ($found) {
            return $found;
        }

        $slug = Str::slug($name, '_');

        $translations = [];

        foreach (core()->getAllLocales() as $locale) {
            $translations[$locale->code] = ['name' => $name];
        }

        return $this->attributeRepository->create([
            ...$translations,
            'code' => self::CODE_PREFIX.($slug !== '' ? Str::limit($slug, 40, '') : substr(md5($wanted), 0, 12)),
            'admin_name' => $name,
            'type' => 'select',
            'position' => 100,
            'is_required' => 0,
            'is_unique' => 0,
            'is_filterable' => 1,
            'is_comparable' => 0,
            'is_configurable' => 1,
            'is_user_defined' => 1,
            'is_visible_on_front' => 1,
            'value_per_channel' => 0,
            'enable_wysiwyg' => 0,
        ]);
    }

    /**
     * The field's option for a typed label, created when it is new.
     */
    protected function optionFor(Attribute $attribute, string $label)
    {
        $wanted = Str::lower($label);

        $found = $attribute->options()->get()->first(fn ($option) => Str::lower((string) $option->admin_name) === $wanted);

        if ($found) {
            return $found;
        }

        $translations = [];

        foreach (core()->getAllLocales() as $locale) {
            $translations[$locale->code] = ['label' => $label];
        }

        return $this->attributeOptionRepository->create([
            ...$translations,
            'attribute_id' => $attribute->id,
            'admin_name' => $label,
            'sort_order' => $attribute->options()->count() + 1,
        ]);
    }

    /**
     * Put a variation field in the family when it is missing, which variants need to read its value.
     */
    protected function attachToFamily(Attribute $attribute, AttributeFamily $family): void
    {
        if ($family->custom_attributes()->where('attributes.id', $attribute->id)->exists()) {
            return;
        }

        $group = $family->attribute_groups()->where('code', self::GROUP_CODE)->first()
            ?? $family->attribute_groups()->create([
                'code' => self::GROUP_CODE,
                'name' => 'Variations',
                'column' => 1,
                'position' => 99,
                'is_user_defined' => 1,
            ]);

        $group->custom_attributes()->attach($attribute->id, ['position' => $group->custom_attributes()->count() + 1]);
    }

    /**
     * The key of a variant's option combination.
     */
    protected function combinationKey(Product $variant, array $attributes): string
    {
        return collect($attributes)
            ->map(fn ($attribute) => $variant->attribute_values->where('attribute_id', $attribute->id)->first()?->integer_value)
            ->implode('|');
    }

    /**
     * Set the picture of a variant from its Variation 1 option: a new file, a copy of the option's picture, or none.
     */
    protected function saveOptionImage(Product $variant, array $option, ?UploadedFile $file, Product $product, int $attributeId, int $optionId, bool $isNew): void
    {
        if ($file) {
            $this->productImageRepository->upload(['images' => ['files' => ['image_0' => $file]]], $variant, 'images');

            return;
        }

        if (empty($option['keep_image'])) {
            $this->productImageRepository->upload([], $variant, 'images');

            return;
        }

        if (! $isNew) {
            return;
        }

        $sibling = $this->productRepository->findWhere(['parent_id' => $product->id])
            ->first(fn ($other) => $other->id !== $variant->id
                && $other->attribute_values->where('attribute_id', $attributeId)->where('integer_value', $optionId)->isNotEmpty()
                && $other->images->isNotEmpty());

        if (! $sibling) {
            return;
        }

        $source = $sibling->images->first()->path;

        $path = 'product/'.$variant->id.'/'.basename($source);

        Storage::copy($source, $path);

        $this->productImageRepository->create(['type' => 'images', 'path' => $path, 'product_id' => $variant->id, 'position' => 1]);
    }
}
