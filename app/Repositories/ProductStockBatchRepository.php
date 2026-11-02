<?php

namespace App\Repositories;

use App\Models\ProductStockBatch;
use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;

class ProductStockBatchRepository extends Repository
{
    /**
     * Specify the model class name.
     */
    public function model(): string
    {
        return ProductStockBatch::class;
    }

    /**
     * Give a product's batches grouped by warehouse, in the order they were added.
     */
    public function forProduct(int $productId): Collection
    {
        return $this->model->newQuery()
            ->where('product_id', $productId)
            ->orderBy('id')
            ->get()
            ->groupBy('inventory_source_id');
    }

    /**
     * Give the quantity held in batches for each of the given products, keyed by product.
     */
    public function totalsForProducts(array $productIds): Collection
    {
        return $this->model->newQuery()
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, SUM(quantity) as total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');
    }

    /**
     * Replace a product's batches with the ones given per warehouse.
     */
    public function replaceForProduct(int $productId, array $batches): void
    {
        $this->model->newQuery()->where('product_id', $productId)->delete();

        foreach ($batches as $sourceId => $rows) {
            foreach ($rows as $row) {
                $this->model->newQuery()->create([
                    'product_id' => $productId,
                    'inventory_source_id' => $sourceId,
                    'quantity' => $row['quantity'],
                    'ship_from' => $row['ship_from'],
                    'ship_to' => $row['ship_to'],
                ]);
            }
        }
    }
}
