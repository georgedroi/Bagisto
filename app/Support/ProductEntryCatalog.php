<?php

namespace App\Support;

use Illuminate\Support\Str;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Contracts\Category;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Inventory\Repositories\InventorySourceRepository;
use Webkul\Product\Repositories\ProductRepository;

class ProductEntryCatalog
{
    /**
     * Create a new catalog helper instance.
     */
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository,
        protected InventorySourceRepository $inventorySourceRepository,
        protected ProductRepository $productRepository
    ) {}

    /**
     * Everything the Add product page needs to draw itself.
     */
    public function pageData(): array
    {
        return [
            'categories' => $this->categories(),
            'families' => $this->families(),
            'productTypes' => $this->productTypes(),
            'numeric' => config('manpleasure-product-entry.numeric_attributes'),
            'rate' => config('manpleasure-product-entry.usd_rate'),
            'currency' => core()->currencySymbol(core()->getBaseCurrencyCode()),
            'admin' => auth()->guard('admin')->user()?->name,
            'urls' => [
                'store' => route('admin.catalog.product_entry.store'),
                'sku' => route('admin.catalog.product_entry.suggest_sku'),
                'back' => route('admin.catalog.products.index'),
            ],
        ];
    }

    /**
     * The main categories with their sub-categories, each main category carrying the family it uses.
     */
    public function categories(): array
    {
        $root = $this->categoryRepository->getVisibleCategoryTree()->first();

        if (! $root) {
            return [];
        }

        $families = config('manpleasure-product-entry.categories');

        $types = config('manpleasure-product-entry.types');

        return $root->children->map(fn ($main) => [
            'id' => $main->id,
            'name' => $main->name,
            'family' => $families[$main->slug]['family'] ?? null,
            'children' => $main->children->map(fn ($sub) => [
                'id' => $sub->id,
                'name' => $sub->name,
                'type' => $types[$sub->slug] ?? null,
            ])->values()->all(),
        ])->values()->all();
    }

    /**
     * Each family's detail fields in the order the family lists them.
     */
    public function families(): array
    {
        $fixed = config('manpleasure-product-entry.fixed_attributes');

        $families = [];

        foreach (collect(config('manpleasure-product-entry.categories'))->pluck('family') as $code) {
            $family = $this->attributeFamilyRepository
                ->with(['attribute_groups.custom_attributes.options'])
                ->findOneByField('code', $code);

            if (! $family) {
                continue;
            }

            $fields = [];

            $hasCost = false;

            foreach ($family->attribute_groups as $group) {
                foreach ($group->custom_attributes as $attribute) {
                    $hasCost = $hasCost || $attribute->code === 'cost';

                    if (in_array($attribute->code, $fixed, true)) {
                        continue;
                    }

                    $fields[] = [
                        'code' => $attribute->code,
                        'label' => $attribute->admin_name,
                        'type' => $attribute->type,
                        'required' => (bool) $attribute->is_required,
                        'options' => $attribute->options->map(fn ($option) => [
                            'value' => $option->id,
                            'label' => $option->admin_name,
                        ])->values()->all(),
                    ];
                }
            }

            $families[$code] = [
                'code' => $code,
                'name' => $family->name,
                'has_cost' => $hasCost,
                'fields' => $fields,
            ];
        }

        return $families;
    }

    /**
     * The values of the product type field.
     */
    public function productTypes(): array
    {
        $attribute = $this->attributeRepository->with('options')->findOneByField('code', 'mp_product_type');

        return $attribute
            ? $attribute->options->map(fn ($option) => ['value' => $option->id, 'label' => $option->admin_name])->values()->all()
            : [];
    }

    /**
     * Work out the main category, family and SKU prefix for a sub-category, or null when it has no family.
     *
     * @return array{sub: Category, main: Category, family: string, prefix: string}|null
     */
    public function resolve(int $categoryId): ?array
    {
        $sub = $this->categoryRepository->find($categoryId);

        if (! $sub || ! $sub->parent_id) {
            return null;
        }

        $main = $this->categoryRepository->find($sub->parent_id);

        if (! $main || ! $main->parent_id) {
            return null;
        }

        $config = config('manpleasure-product-entry.categories')[$main->slug] ?? null;

        if (! $config) {
            return null;
        }

        return [
            'sub' => $sub,
            'main' => $main,
            'family' => $config['family'],
            'prefix' => $config['sku_prefix'],
        ];
    }

    /**
     * The next free SKU for a sub-category, such as MP-LUB-004.
     */
    public function nextSku(int $categoryId): ?string
    {
        $resolved = $this->resolve($categoryId);

        if (! $resolved) {
            return null;
        }

        $prefix = 'MP-'.$resolved['prefix'].'-';

        $highest = $this->productRepository
            ->where('sku', 'like', $prefix.'%')
            ->pluck('sku')
            ->map(fn ($sku) => (int) Str::afterLast($sku, '-'))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($highest + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * The id of the inventory source stock is recorded against.
     */
    public function inventorySourceId(): ?int
    {
        return $this->inventorySourceRepository->findWhere(['status' => 1])->first()?->id;
    }
}
