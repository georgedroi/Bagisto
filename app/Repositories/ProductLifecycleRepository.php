<?php

namespace App\Repositories;

use App\Models\ProductLifecycle;
use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;

class ProductLifecycleRepository extends Repository
{
    /**
     * A product saved as a draft and not finished yet.
     */
    public const DRAFT = 'draft';

    /**
     * A product that is no longer sold but is kept on record.
     */
    public const OBSOLETE = 'obsolete';

    /**
     * Specify the model class name.
     */
    public function model(): string
    {
        return ProductLifecycle::class;
    }

    /**
     * Give the state of each listed product that has one, by product id.
     */
    public function states(array $productIds): Collection
    {
        return $this->model->newQuery()->whereIn('product_id', $productIds)->pluck('state', 'product_id');
    }

    /**
     * Save a product's state, or remove it when no state is given.
     */
    public function saveState(int $productId, ?string $state): void
    {
        if ($state === null) {
            $this->model->newQuery()->where('product_id', $productId)->delete();

            return;
        }

        $this->model->newQuery()->updateOrCreate(['product_id' => $productId], ['state' => $state]);
    }
}
