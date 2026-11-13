<?php

namespace App\Repositories;

use App\Models\ProductStockAlert;
use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;

class ProductStockAlertRepository extends Repository
{
    /**
     * Specify the model class name.
     */
    public function model(): string
    {
        return ProductStockAlert::class;
    }

    /**
     * Give the alert level of each listed product that has one, by product id.
     */
    public function thresholds(array $productIds): Collection
    {
        return $this->model->newQuery()->whereIn('product_id', $productIds)->pluck('threshold', 'product_id');
    }

    /**
     * Save a product's alert level, or remove the alert when no level is given.
     */
    public function saveThreshold(int $productId, ?int $threshold): void
    {
        if ($threshold === null) {
            $this->model->newQuery()->where('product_id', $productId)->delete();

            return;
        }

        $this->model->newQuery()->updateOrCreate(['product_id' => $productId], ['threshold' => $threshold]);
    }
}
