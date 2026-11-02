<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProductEntryRequest;
use App\Repositories\MediaAssetRepository;
use App\Repositories\ProductLifecycleRepository;
use App\Support\ProductEntryCatalog;
use App\Support\ProductVariations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Repositories\ProductRepository;

class ProductEntryController
{
    /**
     * Product fields the Add Product page always shows, so an update saves them even when emptied.
     */
    protected const EDITED_ATTRIBUTES = ['name', 'sku', 'url_key', 'mp_product_type', 'price', 'special_price', 'special_price_from', 'special_price_to', 'weight', 'short_description', 'description', 'status', 'new', 'featured'];

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ProductEntryCatalog $catalog,
        protected ProductRepository $productRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected ProductInventoryRepository $productInventoryRepository,
        protected ProductImageRepository $productImageRepository,
        protected ProductVariations $productVariations,
        protected ProductLifecycleRepository $productLifecycleRepository,
        protected MediaAssetRepository $mediaAssetRepository
    ) {}

    /**
     * Show the Add product page.
     */
    public function index(): View
    {
        return view('manpleasure-admin::product-entry.index', [
            'entry' => $this->catalog->pageData(),
        ]);
    }

    /**
     * Suggest the next free SKU for a category.
     */
    public function suggestSku(Request $request): JsonResponse
    {
        return new JsonResponse([
            'sku' => $this->catalog->nextSku((int) $request->query('category_id')),
        ]);
    }

    /**
     * Store a picture for a product description in the Media Bank and give its address.
     */
    public function storeDescriptionImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $dimensions = @getimagesize($validated['image']->getRealPath());

        abort_unless($dimensions !== false, 422, trans('admin::seller-workspace.add.bad-image'));

        [$asset] = $this->mediaAssetRepository->storeUpload($validated['image'], 'product-description', $dimensions);

        return new JsonResponse(['url' => $this->mediaAssetRepository->publicPath($asset)], 201);
    }

    /**
     * Create the product, then fill it in through the same save the product editor uses.
     * A product with variations is saved as a configurable product with one variant per combination.
     */
    public function store(ProductEntryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $resolved = $this->catalog->resolve((int) $data['category_id']);

        $family = $this->attributeFamilyRepository->findOneByField('code', $resolved['family']);

        $product = DB::transaction(function () use ($data, $resolved, $family) {
            Event::dispatch('catalog.product.create.before');

            $product = $this->productRepository->create([
                'type' => empty($data['variations']) ? 'simple' : 'configurable',
                'attribute_family_id' => $family->id,
                'sku' => $data['sku'],
            ]);

            Event::dispatch('catalog.product.create.after', $product);

            Event::dispatch('catalog.product.update.before', $product->id);

            $product = $this->productRepository->update($this->payload($data, $resolved), $product->id);

            if (! empty($data['variations'])) {
                $this->productVariations->sync($product, $family, $data, $data);
            }

            $this->productLifecycleRepository->saveState($product->id, ! empty($data['draft']) ? ProductLifecycleRepository::DRAFT : null);

            Event::dispatch('catalog.product.update.after', $product);

            return $product;
        });

        session()->flash('success', trans('admin::product-entry.saved'));

        return new JsonResponse([
            'data' => [
                'redirect_url' => route('admin.catalog.products.edit', $product->id),
            ],
        ]);
    }

    /**
     * Save the Add Product page's fields over an existing product, leaving everything the page does not show alone.
     * A category of another main category moves the product to that category's family, and adding or
     * removing variations turns it into a configurable or a simple product.
     */
    public function update(ProductEntryRequest $request, int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('catalog.products.edit'), 403);

        $product = $this->productRepository->findOrFail($id);

        if (! in_array($product->type, ['simple', 'configurable'], true)) {
            return new JsonResponse(['message' => trans('admin::product-pricing.not-simple')], 422);
        }

        $data = $request->validated();

        $resolved = $this->catalog->resolve((int) $data['category_id']);

        $family = $this->catalog->families()[$resolved['family']];

        $familyModel = $this->attributeFamilyRepository->findOneByField('code', $resolved['family']);

        $hasVariations = ! empty($data['variations']);

        $attributes = array_merge(self::EDITED_ATTRIBUTES, $family['has_cost'] ? ['cost'] : [], array_column($family['fields'], 'code'));

        $values = array_merge(array_fill_keys($attributes, ''), array_map(fn ($value) => $value ?? '', Arr::only($data, $attributes)), [
            'channel' => core()->getDefaultChannelCode(),
            'locale' => core()->getDefaultLocaleCodeFromDefaultChannel(),
            'attribute_family_id' => $familyModel->id,
            'new' => (int) ! empty($data['new']),
            'featured' => (int) ! empty($data['featured']),
        ]);

        DB::transaction(function () use ($data, $id, $product, $resolved, $values, $attributes, $familyModel, $hasVariations): void {
            Event::dispatch('catalog.product.update.before', $id);

            if ($product->type === 'configurable' && ! $hasVariations) {
                $this->productVariations->clear($product);
            }

            $product->update(['type' => $hasVariations ? 'configurable' : 'simple']);

            $updated = $this->productRepository->update($values, $id, $attributes);

            $this->productInventoryRepository->saveInventories(['inventories' => [$this->catalog->inventorySourceId() => $hasVariations ? 0 : (int) $data['stock']]], $updated);

            $this->productImageRepository->upload($data, $updated, 'images');

            if (! $product->categories->contains('id', (int) $data['category_id'])) {
                $updated->categories()->sync([$resolved['main']->id, $resolved['sub']->id]);
            }

            if ($hasVariations) {
                $this->productVariations->sync($updated, $familyModel, $data, $values);
            }

            $this->saveLifecycle($id, $data);

            Event::dispatch('catalog.product.update.after', $updated);
        });

        return new JsonResponse(['message' => trans('admin::seller-workspace.actions.saved')]);
    }

    /**
     * Save Draft makes a draft and an online product has no state; an obsolete product stays obsolete while it is off.
     */
    protected function saveLifecycle(int $id, array $data): void
    {
        $isObsolete = (int) $data['status'] === 0
            && $this->productLifecycleRepository->states([$id])->get($id) === ProductLifecycleRepository::OBSOLETE;

        $this->productLifecycleRepository->saveState($id, match (true) {
            ! empty($data['draft']) => ProductLifecycleRepository::DRAFT,
            $isObsolete => ProductLifecycleRepository::OBSOLETE,
            default => null,
        });
    }

    /**
     * Shape the validated input the way the product editor's save expects it.
     */
    protected function payload(array $data, array $resolved): array
    {
        $hasVariations = ! empty($data['variations']);

        return array_merge(Arr::except($data, ['category_id', 'stock', 'draft', 'variations', 'variation_rows', 'variation_images', 'variation_data']), [
            'channel' => core()->getDefaultChannelCode(),
            'locale' => core()->getDefaultLocaleCodeFromDefaultChannel(),
            'visible_individually' => 1,
            'guest_checkout' => 1,
            'manage_stock' => 1,
            'new' => (int) ! empty($data['new']),
            'featured' => (int) ! empty($data['featured']),
            'categories' => [$resolved['main']->id, $resolved['sub']->id],
            'inventories' => $hasVariations ? [] : [$this->catalog->inventorySourceId() => (int) $data['stock']],
        ]);
    }
}
