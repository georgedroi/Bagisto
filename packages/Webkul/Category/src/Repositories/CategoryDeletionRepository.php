<?php

namespace Webkul\Category\Repositories;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Category\Contracts\Category;
use Webkul\Category\Helpers\CategoryTreeOrder;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Repositories\ProductRepository;

class CategoryDeletionRepository extends Repository
{
    /**
     * Resolve the native category model.
     */
    public function model(): string
    {
        return Category::class;
    }

    /**
     * Review a consistent branch and all products that deletion would affect.
     */
    public function review(int $id, array $rootIds): array
    {
        return DB::transaction(fn () => $this->buildPlan($id, $rootIds));
    }

    /**
     * Revalidate the reviewed inventory and delete children before their parents.
     */
    public function deleteReviewed(int $id, array $rootIds, string $revision, bool $deleteProducts, CategoryRepository $categories, ProductRepository $products): array
    {
        return DB::transaction(function () use ($id, $rootIds, $revision, $deleteProducts, $categories, $products) {
            $plan = $this->buildPlan($id, $rootIds, true);

            if (! hash_equals($plan['revision'], $revision)) {
                throw new DomainException('stale');
            }

            if ($deleteProducts) {
                foreach ($plan['product_ids'] as $productId) {
                    Event::dispatch('catalog.product.delete.before', $productId);

                    if (! $products->delete($productId)) {
                        throw new DomainException('delete-failed');
                    }
                }
            }

            foreach ($plan['category_ids'] as $categoryId) {
                Event::dispatch('catalog.category.delete.before', $categoryId);

                if (! $categories->delete($categoryId)) {
                    throw new DomainException('delete-failed');
                }
            }

            return [
                'category_ids' => $plan['category_ids'],
                'product_ids' => $deleteProducts ? $plan['product_ids'] : [],
                'parent_id' => $plan['parent_id'],
            ];
        }, 3);
    }

    /**
     * Include product descendants and outside-category assignments in the review revision.
     */
    protected function buildPlan(int $id, array $rootIds, bool $lock = false): array
    {
        $categoryQuery = $this->model->newQuery()->orderBy('id');

        if ($lock) {
            $categoryQuery->lockForUpdate();
        }

        $categories = $categoryQuery->get()->keyBy('id');
        $node = $categories->get($id);

        if (! $node) {
            throw new DomainException('invalid');
        }

        $branch = [$id];
        $frontier = [$id];

        while ($frontier) {
            $children = $categories->filter(fn ($category) => in_array((int) $category->parent_id, $frontier, true))->keys()->map(fn ($id) => (int) $id)->all();

            if (array_intersect($children, $branch)) {
                throw new DomainException('cycle');
            }

            $branch = array_merge($branch, $children);
            $frontier = $children;
        }

        if ($node->parent_id === null || array_intersect($branch, array_merge([1], array_map('intval', $rootIds)))) {
            throw new DomainException('root');
        }

        $assignmentsQuery = DB::table('product_categories')->whereIn('category_id', $branch)->orderBy('product_id')->orderBy('category_id');

        if ($lock) {
            $assignmentsQuery->lockForUpdate();
        }

        $assignedIds = $assignmentsQuery->get()->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $productRows = collect();
        $frontier = $assignedIds;

        while ($frontier) {
            $query = DB::table('products')->select('id', 'parent_id', 'sku', 'type', 'updated_at')
                ->where(fn ($query) => $query->whereIn('id', $frontier)->orWhereIn('parent_id', $frontier))
                ->orderBy('id');

            if ($lock) {
                $query->lockForUpdate();
            }

            $found = $query->get()->keyBy('id');
            $new = $found->diffKeys($productRows);
            $productRows = $productRows->union($new);
            $frontier = $new->keys()->map(fn ($id) => (int) $id)->all();
        }

        $productRows = $productRows->sortKeys();
        $linksQuery = DB::table('product_categories')->whereIn('product_id', $productRows->keys())->orderBy('product_id')->orderBy('category_id');

        if ($lock) {
            $linksQuery->lockForUpdate();
        }

        $links = $linksQuery->get();
        $depth = static function ($product) use ($productRows): int {
            $path = [];

            while ($product && $product->parent_id !== null && $productRows->has((int) $product->parent_id)) {
                if (in_array((int) $product->id, $path, true)) {
                    throw new DomainException('cycle');
                }

                $path[] = (int) $product->id;
                $product = $productRows->get((int) $product->parent_id);
            }

            return count($path);
        };
        $productIds = $productRows->sort(fn ($a, $b) => [$depth($b), (int) $b->id] <=> [$depth($a), (int) $a->id])->keys()->map(fn ($id) => (int) $id)->all();
        $branchModels = $categories->only($branch)->sortByDesc('_lft');
        $categoryState = $categories->map(fn ($category) => [$category->getAttributes(), (string) $category->name])->all();
        $revision = hash('sha256', json_encode([CategoryTreeOrder::revision($categories), $categoryState, $productRows->values()->all(), $links->all()], JSON_THROW_ON_ERROR));

        return [
            'id' => $id,
            'name' => (string) $node->name,
            'parent_id' => (int) $node->parent_id,
            'revision' => $revision,
            'category_count' => count($branch),
            'product_count' => $productRows->count(),
            'assigned_count' => count($assignedIds),
            'variant_count' => $productRows->count() - count($assignedIds),
            'shared_count' => $links->whereNotIn('category_id', $branch)->pluck('product_id')->unique()->count(),
            'categories' => $branchModels->reverse()->take(50)->map(fn ($category) => ['id' => (int) $category->id, 'name' => (string) $category->name])->values()->all(),
            'products' => $productRows->take(50)->map(fn ($product) => ['id' => (int) $product->id, 'sku' => $product->sku])->values()->all(),
            'category_ids' => $branchModels->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'product_ids' => $productIds,
        ];
    }
}
