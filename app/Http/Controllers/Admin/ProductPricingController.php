<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProductPricingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Product\Facades\ProductImage;
use Webkul\Product\Repositories\ProductRepository;

class ProductPricingController
{
    /**
     * Product types whose price is stored on the product itself, so it can be edited from the list.
     */
    protected const PRICED_TYPES = ['simple', 'virtual'];

    /**
     * Create a new controller instance.
     */
    public function __construct(protected ProductRepository $productRepository) {}

    /**
     * Give the pricing of one product, with what the pricing window shows beside it.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->findOrFail($id);

        if (! in_array($product->type, self::PRICED_TYPES, true)) {
            return new JsonResponse(['message' => trans('admin::product-pricing.not-simple')], 422);
        }

        return new JsonResponse(['data' => $this->summary($product)]);
    }

    /**
     * Save the price, special price with its dates and cost, touching no other field of the product.
     */
    public function update(ProductPricingRequest $request, int $id): JsonResponse
    {
        $product = $this->productRepository->findOrFail($id);

        if (! in_array($product->type, self::PRICED_TYPES, true)) {
            return new JsonResponse(['message' => trans('admin::product-pricing.not-simple')], 422);
        }

        $attributes = ['price', 'special_price', 'special_price_from', 'special_price_to'];

        $data = [
            'price' => $request->input('price'),
            'special_price' => $request->input('special_price') ?? '',
            'special_price_from' => $request->input('special_price_from') ?? '',
            'special_price_to' => $request->input('special_price_to') ?? '',
            'channel' => core()->getDefaultChannelCode(),
            'locale' => core()->getDefaultLocaleCodeFromDefaultChannel(),
        ];

        if ($request->has('cost') && $this->hasCostField($product)) {
            $attributes[] = 'cost';

            $data['cost'] = $request->input('cost') ?? '';
        }

        DB::transaction(function () use ($data, $id, $attributes): void {
            Event::dispatch('catalog.product.update.before', $id);

            $updated = $this->productRepository->update($data, $id, $attributes);

            Event::dispatch('catalog.product.update.after', $updated);
        });

        return new JsonResponse([
            'message' => trans('admin::product-pricing.saved'),
            'data' => $this->summary($this->productRepository->findOrFail($id)),
        ]);
    }

    /**
     * What the pricing window needs to draw itself for a product.
     */
    protected function summary($product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'image' => ProductImage::getProductBaseImage($product)['small_image_url'] ?? null,
            'stock' => (int) $product->inventories()->sum('qty'),
            'price' => $product->price !== null ? (float) $product->price : null,
            'special_price' => $product->special_price !== null ? (float) $product->special_price : null,
            'special_price_from' => $product->special_price_from,
            'special_price_to' => $product->special_price_to,
            'cost' => $product->cost !== null ? (float) $product->cost : null,
            'has_cost_field' => $this->hasCostField($product),
            'status' => (bool) $product->status,
            'currency' => core()->currencySymbol(core()->getBaseCurrencyCode()),
            'storefront_url' => $product->url_key ? route('shop.product_or_category.index', $product->url_key) : null,
        ];
    }

    /**
     * Whether the product's family has the Cost field.
     */
    protected function hasCostField($product): bool
    {
        return $product->attribute_family->custom_attributes->contains('code', 'cost');
    }
}
