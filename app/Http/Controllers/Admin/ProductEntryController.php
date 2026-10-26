<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProductEntryRequest;
use App\Support\ProductEntryCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Product\Repositories\ProductRepository;

class ProductEntryController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ProductEntryCatalog $catalog,
        protected ProductRepository $productRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository
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
     * Create the product, then fill it in through the same save the product editor uses.
     */
    public function store(ProductEntryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $resolved = $this->catalog->resolve((int) $data['category_id']);

        $family = $this->attributeFamilyRepository->findOneByField('code', $resolved['family']);

        $product = DB::transaction(function () use ($data, $resolved, $family) {
            Event::dispatch('catalog.product.create.before');

            $product = $this->productRepository->create([
                'type' => 'simple',
                'attribute_family_id' => $family->id,
                'sku' => $data['sku'],
            ]);

            Event::dispatch('catalog.product.create.after', $product);

            Event::dispatch('catalog.product.update.before', $product->id);

            $product = $this->productRepository->update($this->payload($data, $resolved), $product->id);

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
     * Shape the validated input the way the product editor's save expects it.
     */
    protected function payload(array $data, array $resolved): array
    {
        return array_merge(Arr::except($data, ['category_id', 'stock']), [
            'channel' => core()->getDefaultChannelCode(),
            'locale' => core()->getDefaultLocaleCodeFromDefaultChannel(),
            'visible_individually' => 1,
            'guest_checkout' => 1,
            'manage_stock' => 1,
            'new' => (int) ! empty($data['new']),
            'featured' => (int) ! empty($data['featured']),
            'categories' => [$resolved['main']->id, $resolved['sub']->id],
            'inventories' => [$this->catalog->inventorySourceId() => (int) $data['stock']],
        ]);
    }
}
