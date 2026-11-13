<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProductStockRequest;
use App\Repositories\ProductStockAlertRepository;
use App\Repositories\ProductStockBatchRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Inventory\Repositories\InventorySourceRepository;
use Webkul\Product\Facades\ProductImage;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Repositories\ProductRepository;

class ProductStockController
{
    /**
     * Product types that hold their own stock, so it can be edited from the list.
     */
    protected const STOCKED_TYPES = ['simple', 'virtual'];

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductInventoryRepository $productInventoryRepository,
        protected InventorySourceRepository $inventorySourceRepository,
        protected ProductStockAlertRepository $productStockAlertRepository,
        protected ProductStockBatchRepository $productStockBatchRepository
    ) {}

    /**
     * Give the stock of one product for each active warehouse.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->findOrFail($id);

        if (! in_array($product->type, self::STOCKED_TYPES, true)) {
            return new JsonResponse(['message' => trans('admin::product-pricing.stock-not-simple')], 422);
        }

        return new JsonResponse(['data' => $this->summary($product)]);
    }

    /**
     * Save the stock per warehouse and, when the form manages them, its batches, touching no other field of the product.
     */
    public function update(ProductStockRequest $request, int $id): JsonResponse
    {
        $product = $this->productRepository->findOrFail($id);

        if (! in_array($product->type, self::STOCKED_TYPES, true)) {
            return new JsonResponse(['message' => trans('admin::product-pricing.stock-not-simple')], 422);
        }

        DB::transaction(function () use ($request, $product, $id): void {
            Event::dispatch('catalog.product.update.before', $id);

            $this->productInventoryRepository->saveInventories(['inventories' => $request->input('inventories')], $product);

            if ($request->boolean('batches_sync')) {
                $this->productStockBatchRepository->replaceForProduct($id, $request->input('batches', []));
            }

            Event::dispatch('catalog.product.update.after', $product);
        });

        return new JsonResponse([
            'message' => trans('admin::product-pricing.stock-saved'),
            'data' => $this->summary($this->productRepository->findOrFail($id)),
        ]);
    }

    /**
     * Save the stock level below which a product's alert is raised; switching it off removes the alert.
     */
    public function alert(int $id): JsonResponse
    {
        $data = request()->validate([
            'enabled' => ['nullable', 'boolean'],
            'threshold' => ['required_if_accepted:enabled', 'nullable', 'integer', 'min:1', 'max:2147483647'],
        ]);

        $product = $this->productRepository->findOrFail($id);

        if (! in_array($product->type, self::STOCKED_TYPES, true)) {
            return new JsonResponse(['message' => trans('admin::product-pricing.stock-not-simple')], 422);
        }

        $this->productStockAlertRepository->saveThreshold($id, request()->boolean('enabled') ? (int) $data['threshold'] : null);

        return new JsonResponse(['message' => trans('admin::seller-workspace.actions.saved')]);
    }

    /**
     * What the stock window needs to draw itself for a product.
     */
    protected function summary($product): array
    {
        $inventories = $product->inventories()->pluck('qty', 'inventory_source_id');

        $batches = $this->productStockBatchRepository->forProduct($product->id);

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'image' => ProductImage::getProductBaseImage($product)['small_image_url'] ?? null,
            'total' => (int) $inventories->sum(),
            'sources' => $this->inventorySourceRepository->findWhere(['status' => 1])
                ->map(fn ($source) => [
                    'id' => $source->id,
                    'name' => $source->name,
                    'qty' => (int) ($inventories[$source->id] ?? 0),
                    'batches' => collect($batches[$source->id] ?? [])
                        ->map(fn ($batch) => [
                            'quantity' => $batch->quantity,
                            'ship_from' => $batch->ship_from->toDateString(),
                            'ship_to' => $batch->ship_to->toDateString(),
                        ])
                        ->values(),
                ])
                ->values(),
        ];
    }
}
