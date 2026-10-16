<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Admin\DataGrids\Catalog\CategoryDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\CategoryDeletePreviewRequest;
use Webkul\Admin\Http\Requests\CategoryDeleteRequest;
use Webkul\Admin\Http\Requests\CategoryMoveRequest;
use Webkul\Admin\Http\Requests\CategoryRequest;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Admin\Http\Resources\CategoryTreeResource;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryDeletionRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shop\Helpers\CatalogApiCache;

class CategoryController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CategoryRepository $categoryRepository,
        protected AttributeRepository $attributeRepository
    ) {}

    /**
     * Display the complete category hierarchy or the existing datagrid.
     *
     * @return View|JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(CategoryDataGrid::class)->process();
        }

        $snapshot = request()->query('view') === 'list'
            ? ['categories' => collect()]
            : $this->categoryRepository->getAdminTreeSnapshot($this->channelRepository->pluck('root_category_id')->all());

        return view('admin::catalog.categories.index', $snapshot);
    }

    /**
     * Save a tree move without modifying category content or product assignments.
     */
    public function organize(CategoryMoveRequest $request): JsonResponse
    {
        $data = $request->validated();
        $roots = $this->channelRepository->pluck('root_category_id')->all();

        try {
            $changed = $this->categoryRepository->organizeTree(
                (int) $data['category_id'],
                (int) $data['parent_id'],
                isset($data['before_id']) ? (int) $data['before_id'] : null,
                $data['revision'],
                $roots
            );
        } catch (DomainException $exception) {
            return new JsonResponse([
                'message' => trans('admin::category-tree.error-'.$exception->getMessage()),
            ], $exception->getMessage() === 'stale' ? 409 : 422);
        }

        foreach ($changed as $category) {
            Event::dispatch('catalog.category.update.after', $category);
        }

        return new JsonResponse([
            'message' => trans('admin::category-tree.saved'),
            'html' => view('admin::catalog.categories.tree', $this->categoryRepository->getAdminTreeSnapshot($roots))->render(),
        ]);
    }

    /**
     * Review the exact deletion scope without changing catalog data.
     */
    public function reviewDeletion(CategoryDeletePreviewRequest $request, CategoryDeletionRepository $deletions): JsonResponse
    {
        try {
            $plan = $deletions->review((int) $request->validated('category_id'), $this->channelRepository->pluck('root_category_id')->all());
        } catch (DomainException $exception) {
            return new JsonResponse(['message' => trans('admin::category-tree.error-'.$exception->getMessage())], 422);
        }

        $token = Str::random(40);
        $reviews = array_filter($request->session()->get('category_tree_delete', []), fn ($review) => $review['expires'] >= time());
        $reviews = array_slice($reviews, -9, null, true);
        $reviews[$token] = [
            'id' => $plan['id'],
            'revision' => $plan['revision'],
            'expires' => time() + 600,
        ];
        $request->session()->put('category_tree_delete', $reviews);

        return new JsonResponse([
            'review' => array_intersect_key($plan, array_flip(['id', 'name', 'revision', 'category_count', 'product_count', 'assigned_count', 'variant_count', 'shared_count', 'categories', 'products'])),
            'preview_token' => $token,
            'can_delete_products' => bouncer()->hasPermission('catalog.products.delete'),
        ]);
    }

    /**
     * Delete only a reviewed branch and the explicitly selected product inventory.
     */
    public function deleteTree(CategoryDeleteRequest $request, CategoryDeletionRepository $deletions, ProductRepository $products, CatalogApiCache $catalogCache): JsonResponse
    {
        $data = $request->validated();
        $reviewKey = 'category_tree_delete.'.$data['preview_token'];
        $review = $request->session()->get($reviewKey);

        if (! $review || $review['expires'] < time() || $review['id'] !== (int) $data['category_id'] || ! hash_equals($review['revision'], $data['revision'])) {
            return new JsonResponse(['message' => trans('admin::category-tree.error-delete-review')], 409);
        }

        try {
            $result = $deletions->deleteReviewed((int) $data['category_id'], $this->channelRepository->pluck('root_category_id')->all(), $data['revision'], $request->boolean('delete_products'), $this->categoryRepository, $products);
        } catch (DomainException $exception) {
            return new JsonResponse(['message' => trans('admin::category-tree.error-'.$exception->getMessage())], 409);
        }

        Log::notice('Category tree deletion approved', [
            'actor_admin_id' => auth('admin')->id(),
            'approver_admin_id' => $request->approvedBy(),
            'category_ids' => $result['category_ids'],
            'product_ids' => $result['product_ids'],
        ]);

        $request->session()->forget($reviewKey);

        foreach ($result['product_ids'] as $id) {
            Event::dispatch('catalog.product.delete.after', $id);
        }

        foreach ($result['category_ids'] as $id) {
            Event::dispatch('catalog.category.delete.after', $id);
        }

        $catalogCache->flush();
        ResponseCache::clear();

        return new JsonResponse([
            'message' => trans('admin::category-tree.deleted', ['categories' => count($result['category_ids']), 'products' => count($result['product_ids'])]),
            'focus_id' => $result['parent_id'],
            'html' => view('admin::catalog.categories.tree', $this->categoryRepository->getAdminTreeSnapshot($this->channelRepository->pluck('root_category_id')->all()))->render(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        $categories = $this->categoryRepository->getCategoryTree();

        $attributes = $this->attributeRepository->findWhere(['is_filterable' => 1]);

        $selectedParentId = request()->filled('parent_id')
            ? (int) $this->categoryRepository->findOrFail(request()->integer('parent_id'))->id
            : (int) $this->channelRepository->pluck('root_category_id')->first();

        return view('admin::catalog.categories.create', compact('categories', 'attributes', 'selectedParentId'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(CategoryRequest $categoryRequest)
    {
        Event::dispatch('catalog.category.create.before');

        $data = $categoryRequest->only([
            'locale',
            'name',
            'parent_id',
            'description',
            'slug',
            'meta_title',
            'meta_keywords',
            'meta_description',
            'status',
            'position',
            'display_mode',
            'attributes',
            'logo_path',
            'logo_meta',
            'banner_path',
            'banner_meta',
        ]);

        if (! empty($data['description'])) {
            $data['description'] = clean_content($data['description']);
        }

        $category = $this->categoryRepository->create($data);

        Event::dispatch('catalog.category.create.after', $category);

        session()->flash('success', trans('admin::app.catalog.categories.create-success'));

        return redirect()->route('admin.catalog.categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return View
     */
    public function edit(int $id)
    {
        $category = $this->categoryRepository->findOrFail($id);

        $categories = $this->categoryRepository->getCategoryTreeWithoutDescendant($id);

        $attributes = $this->attributeRepository->findWhere(['is_filterable' => 1]);

        return view('admin::catalog.categories.edit', compact('category', 'categories', 'attributes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(CategoryRequest $categoryRequest, int $id)
    {
        Event::dispatch('catalog.category.update.before', $id);

        $locale = $categoryRequest->input('locale');

        $localeData = $categoryRequest->input($locale);

        if (! empty($localeData['description'])) {
            $localeData['description'] = clean_content($localeData['description']);
        }

        $data = $categoryRequest->only(
            'locale',
            'parent_id',
            'logo_path',
            'logo_meta',
            'banner_path',
            'banner_meta',
            'position',
            'display_mode',
            'status',
            'attributes'
        );

        $data[$locale] = $localeData;

        $category = $this->categoryRepository->update($data, $id);

        Event::dispatch('catalog.category.update.after', $category);

        session()->flash('success', trans('admin::app.catalog.categories.update-success'));

        return redirect()->route('admin.catalog.categories.index');
    }

    /**
     * Refuse unreviewed deletion from older admin clients.
     */
    public function destroy(int $id): JsonResponse
    {
        return new JsonResponse(['message' => trans('admin::category-tree.error-delete-use-tree')], 409);
    }

    /**
     * Require a separate scope review and password confirmation for each branch.
     */
    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        return new JsonResponse(['message' => trans('admin::category-tree.error-delete-use-tree')], 409);
    }

    /**
     * Mass update Category.
     *
     * @return JsonResponse
     */
    public function massUpdate(MassUpdateRequest $massUpdateRequest)
    {
        try {
            $categoryIds = $massUpdateRequest->input('indices');

            foreach ($categoryIds as $categoryId) {
                Event::dispatch('catalog.categories.mass-update.before', $categoryId);

                $category = $this->categoryRepository->find($categoryId);

                $category->status = $massUpdateRequest->input('value');

                $category->save();

                Event::dispatch('catalog.categories.mass-update.after', $category);
            }

            return new JsonResponse([
                'message' => trans('admin::app.catalog.categories.update-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all categories in tree format.
     */
    public function tree(): JsonResource
    {
        $categories = $this->categoryRepository->getVisibleCategoryTree(core()->getRequestedChannel()->root_category_id);

        return CategoryTreeResource::collection($categories);
    }

    /**
     * Get all the searched categories.
     *
     * @return JsonResponse
     */
    public function search()
    {
        $categories = $this->categoryRepository->getAll([
            'name' => request()->input('query'),
            'locale' => app()->getLocale(),
        ]);

        return response()->json($categories);
    }
}
