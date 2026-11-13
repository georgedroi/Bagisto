<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder;
use Webkul\Product\Repositories\ProductFlatRepository;

class ProductStockRepository extends ProductFlatRepository
{
    /**
     * Product list filters shared by the dashboard task list and the product grid.
     */
    public const FILTERS = [
        'out' => 'admin::executive-layout.out-stock',
        'low' => 'admin::executive-layout.low-stock',
        'inactive' => 'admin::executive-layout.inactive',
    ];

    /**
     * Manage Products tabs in menu order, each with the status and lifecycle state it lists.
     * Online and All ignore the state, and Offline lists switched off products that have none.
     */
    public const PRODUCT_TABS = [
        'active' => ['status' => 1, 'state' => null],
        'inactive' => ['status' => 0, 'state' => self::NO_STATE],
        'draft' => ['status' => 0, 'state' => ProductLifecycleRepository::DRAFT],
        'obsolete' => ['status' => 0, 'state' => ProductLifecycleRepository::OBSOLETE],
        'all' => ['status' => null, 'state' => null],
    ];

    /**
     * Lists products with no lifecycle state.
     */
    public const NO_STATE = 'none';

    /**
     * Highest quantity that still counts as low stock.
     */
    public const LOW_STOCK_LIMIT = 10;

    /**
     * Count each filter using the same constraints as the product list.
     */
    public function stockCounts(): array
    {
        $counts = [];

        foreach (array_keys(self::FILTERS) as $filter) {
            $query = $this->model->newQuery()->where('product_flat.locale', app()->getLocale());

            $counts[$filter] = $this->constrainStock($query, $filter)
                ->distinct()
                ->count('product_flat.product_id');
        }

        return $counts;
    }

    /**
     * Count products by status, the same way the product list does.
     */
    public function statusCount(int $status): int
    {
        return $this->model->newQuery()
            ->where('product_flat.locale', app()->getLocale())
            ->where('product_flat.status', $status)
            ->distinct()
            ->count('product_flat.product_id');
    }

    /**
     * Page through products by status, lifecycle state, name or SKU search, out or low stock filter and raised stock alerts.
     */
    public function paginateList(?int $status, ?string $search, ?string $stock, int $perPage, bool $alerted = false, ?string $state = null): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->select('product_flat.*')
            ->where('product_flat.locale', app()->getLocale())
            ->groupBy('product_flat.product_id')
            ->orderByDesc('product_flat.product_id');

        if ($status !== null) {
            $query->where('product_flat.status', $status);
        }

        if ($state !== null) {
            $this->constrainState($query, $state);
        }

        if (in_array($stock, ['out', 'low'], true)) {
            $this->constrainStock($query, $stock);
        }

        if ($alerted) {
            $this->constrainAlerted($query);
        }

        if ($search !== null && $search !== '') {
            $term = '%'.addcslashes($search, '\\%_').'%';

            $query->where(fn ($query) => $query->where('product_flat.name', 'like', $term)->orWhere('product_flat.sku', 'like', $term));
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * List the products with the least stock that count as out or low, lowest first.
     */
    public function lowestStock(int $limit): EloquentCollection
    {
        $query = $this->model->newQuery()
            ->select('product_flat.*')
            ->where('product_flat.locale', app()->getLocale());

        return $this->constrainStocked($query)
            ->where(fn ($query) => $query->whereNull('product_flat.quantity')->orWhere('product_flat.quantity', '<=', self::LOW_STOCK_LIMIT))
            ->groupBy('product_flat.product_id')
            ->orderByRaw('COALESCE(product_flat.quantity, 0)')
            ->limit($limit)
            ->get();
    }

    /**
     * Count products whose stock has fallen below their alert level.
     */
    public function alertedCount(): int
    {
        return $this->constrainAlerted($this->model->newQuery()->where('product_flat.locale', app()->getLocale()))
            ->distinct()
            ->count('product_flat.product_id');
    }

    /**
     * Apply a stock filter. Online and offline products both count as out or low; obsolete products
     * and products that hold no stock of their own never do.
     */
    public function constrainStock(Builder|EloquentBuilder $query, string $filter): Builder|EloquentBuilder
    {
        if ($filter === 'inactive') {
            return $this->constrainState($query->where('product_flat.status', 0), self::NO_STATE);
        }

        if (! array_key_exists($filter, self::FILTERS)) {
            return $query;
        }

        $this->constrainStocked($query);

        if ($filter === 'low') {
            return $query->whereBetween('product_flat.quantity', [1, self::LOW_STOCK_LIMIT]);
        }

        return $query->where(fn ($query) => $query->whereNull('product_flat.quantity')->orWhere('product_flat.quantity', '<=', 0));
    }

    /**
     * Keep only products with this lifecycle state, or with none.
     */
    protected function constrainState(Builder|EloquentBuilder $query, string $state): Builder|EloquentBuilder
    {
        $states = fn ($sub) => $sub->selectRaw('1')
            ->from('product_lifecycles')
            ->whereColumn('product_lifecycles.product_id', 'product_flat.product_id')
            ->when($state !== self::NO_STATE, fn ($sub) => $sub->where('product_lifecycles.state', $state));

        return $state === self::NO_STATE ? $query->whereNotExists($states) : $query->whereExists($states);
    }

    /**
     * Keep only products whose stock has fallen below their alert level.
     */
    protected function constrainAlerted(EloquentBuilder $query): EloquentBuilder
    {
        return $query->join('product_stock_alerts', 'product_stock_alerts.product_id', '=', 'product_flat.product_id')
            ->whereRaw('COALESCE(product_flat.quantity, 0) < product_stock_alerts.threshold');
    }

    /**
     * Keep only products that hold managed stock of their own and are not obsolete.
     */
    protected function constrainStocked(Builder|EloquentBuilder $query): Builder|EloquentBuilder
    {
        return $query->whereIn('product_flat.type', ['simple', 'virtual'])
            ->whereNotExists(fn ($sub) => $sub->selectRaw('1')
                ->from('product_lifecycles')
                ->whereColumn('product_lifecycles.product_id', 'product_flat.product_id')
                ->where('product_lifecycles.state', ProductLifecycleRepository::OBSOLETE))
            ->where(fn ($query) => $query->whereNull('product_flat.manage_stock')->orWhere('product_flat.manage_stock', 1));
    }
}
