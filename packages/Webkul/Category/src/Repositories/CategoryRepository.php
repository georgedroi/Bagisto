<?php

namespace Webkul\Category\Repositories;

use DomainException;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Category\Contracts\Category;
use Webkul\Category\Helpers\CategoryTreeOrder;
use Webkul\Category\Models\CategoryTranslationProxy;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Helpers\MediaFileName;

class CategoryRepository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected MediaFileName $mediaFileName,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Category::class;
    }

    /**
     * Get categories.
     *
     * @return void
     */
    public function getAll(array $params = [])
    {
        $queryBuilder = $this->query()
            ->select('categories.*')
            ->leftJoin('category_translations', 'category_translations.category_id', '=', 'categories.id');

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'name':
                    $queryBuilder->where('category_translations.name', 'like', '%'.urldecode($value).'%');

                    break;
                case 'description':
                    $queryBuilder->where('category_translations.description', 'like', '%'.urldecode($value).'%');

                    break;
                case 'status':
                    $queryBuilder->where('categories.status', $value);

                    break;
                case 'only_children':
                    $queryBuilder->whereNotNull('categories.parent_id');

                    break;
                case 'parent_id':
                    $parentIds = array_filter(array_map('trim', explode(',', $value)));
                    $queryBuilder->whereIn('categories.parent_id', $parentIds);

                    break;
                case 'locale':
                    $queryBuilder->where('category_translations.locale', $value);

                    break;
            }
        }

        $direction = ($params['sort'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $queryBuilder->orderBy('categories.position', $direction)
            ->orderBy('categories.id', $direction)
            ->paginate($params['limit'] ?? 10);
    }

    /**
     * Create category.
     *
     * @return Category
     */
    public function create(array $data)
    {
        if (
            isset($data['locale'])
            && $data['locale'] == 'all'
        ) {
            $model = app()->make($this->model());

            foreach (core()->getAllLocales() as $locale) {
                foreach ($model->translatedAttributes as $attribute) {
                    if (isset($data[$attribute])) {
                        $data[$locale->code][$attribute] = $data[$attribute];

                        $data[$locale->code]['locale_id'] = $locale->id;
                    }
                }
            }
        }

        $category = $this->model->create($data);

        $this->uploadImages($data, $category);

        $this->uploadImages($data, $category, 'banner_path');

        if (isset($data['attributes'])) {
            $category->filterableAttributes()->sync($data['attributes']);
        }

        return $category;
    }

    /**
     * Update category.
     *
     * @param  int  $id
     * @return Category
     */
    public function update(array $data, $id)
    {
        $category = $this->find($id);

        $data = $this->setSameAttributeValueToAllLocale($data, 'slug');

        $category->update($data);

        $this->uploadImages($data, $category);

        $this->uploadImages($data, $category, 'banner_path');

        if (isset($data['attributes'])) {
            $category->filterableAttributes()->sync($data['attributes']);
        }

        return $category;
    }

    /**
     * Retrieve category from slug.
     *
     * @param  string  $slug
     * @return Category
     */
    public function findBySlug($slug)
    {
        if ($category = $this->model->whereTranslation('slug', $slug)->first()) {
            return $category;
        }
    }

    /**
     * Retrieve category from slug.
     *
     * @param  string  $slug
     * @return Category
     */
    public function findBySlugOrFail($slug)
    {
        return $this->model->whereTranslation('slug', $slug)->firstOrFail();
    }

    /**
     * Render one consistent hierarchy and its concurrency revision.
     */
    public function getAdminTreeSnapshot(array $channelRootIds): array
    {
        $categories = $this->model->newQuery()->orderBy('position')->orderBy('id')->get();

        return [
            'revision' => CategoryTreeOrder::revision($categories),
            'channelRootIds' => array_map('intval', $channelRootIds),
            'categories' => $categories->toTree(),
        ];
    }

    /**
     * Move a complete subtree and persist deterministic sibling positions atomically.
     */
    public function organizeTree(int $id, int $parentId, ?int $beforeId, string $revision, array $channelRootIds): array
    {
        return DB::transaction(function () use ($id, $parentId, $beforeId, $revision, $channelRootIds) {
            $categories = $this->model->newQuery()->orderBy('id')->lockForUpdate()->get();

            if (! hash_equals(CategoryTreeOrder::revision($categories), $revision)) {
                throw new DomainException('stale');
            }

            $orders = CategoryTreeOrder::plan($categories, $id, $parentId, $beforeId, $channelRootIds);
            $node = $categories->find($id);
            $oldParentId = (int) $node->parent_id;

            if ($oldParentId !== $parentId) {
                $node->appendToNode($categories->find($parentId))->saveOrFail();
            }

            $changedIds = [$id, $oldParentId, $parentId];

            foreach ($orders as $order) {
                foreach ($order as $position => $categoryId) {
                    $category = $categories->find($categoryId);

                    if ((int) $category->position !== $position + 1) {
                        $category->position = $position + 1;
                        $category->saveOrFail();
                        $changedIds[] = $categoryId;
                    }
                }
            }

            return $this->model->newQuery()->whereIn('id', array_unique($changedIds))->get()->all();
        });
    }

    /**
     * Get root categories.
     *
     * @return Collection
     */
    public function getRootCategories()
    {
        return $this->getModel()->where('parent_id', null)->get();
    }

    /**
     * Get child categories.
     *
     * @return Collection
     */
    public function getChildCategories($parentId)
    {
        return $this->getModel()->where('parent_id', $parentId)->get();
    }

    /**
     * Specify category tree.
     *
     * @return Category
     */
    public function getCategoryTree(?int $id = null)
    {
        return $id
            ? $this->model::orderBy('position', 'ASC')->orderBy('id', 'ASC')->where('id', '!=', $id)->get()->toTree()
            : $this->model::orderBy('position', 'ASC')->orderBy('id', 'ASC')->get()->toTree();
    }

    /**
     * Specify category tree.
     *
     * @return Collection
     */
    public function getCategoryTreeWithoutDescendant(?int $id = null)
    {
        return $id
            ? $this->model::orderBy('position', 'ASC')->orderBy('id', 'ASC')->where('id', '!=', $id)->whereNotDescendantOf($id)->get()->toTree()
            : $this->model::orderBy('position', 'ASC')->orderBy('id', 'ASC')->get()->toTree();
    }

    /**
     * get visible category tree.
     *
     * @param  int  $id
     * @return Collection
     */
    public function getVisibleCategoryTree($id = null)
    {
        return $id
            ? $this->model::orderBy('position', 'ASC')->orderBy('id', 'ASC')->where('status', 1)->descendantsAndSelf($id)->toTree($id)
            : $this->model::orderBy('position', 'ASC')->orderBy('id', 'ASC')->where('status', 1)->get()->toTree();
    }

    /**
     * Get the IDs of visible categories under the given root (inclusive).
     *
     * @param  int|null  $rootId
     * @return array
     */
    public function getVisibleCategoryIds($rootId = null)
    {
        $query = $this->model::where('status', 1);

        if ($rootId) {
            $query = $query->descendantsAndSelf($rootId);
        }

        return $query->pluck('id')->all();
    }

    /**
     * Get partials.
     *
     * @param  array|null  $columns
     * @return array
     */
    public function getPartial($columns = null)
    {
        $categories = $this->model->all();

        $trimmed = [];

        foreach ($categories as $key => $category) {
            if (! empty($category->name)) {
                $trimmed[$key] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ];
            }
        }

        return $trimmed;
    }

    /**
     * Checks slug is unique or not based on locale.
     *
     * @param  int  $id
     * @param  string  $slug
     * @return bool
     */
    public function isSlugUnique($id, $slug)
    {
        $exists = CategoryTranslationProxy::modelClass()::where('category_id', '<>', $id)
            ->where('slug', $slug)
            ->limit(1)
            ->select(DB::raw(1))
            ->exists();

        return ! $exists;
    }

    /**
     * Upload category's images.
     *
     * @param  array  $data
     * @param  Category  $category
     * @param  string  $type
     * @return void
     */
    public function uploadImages($data, $category, $type = 'logo_path')
    {
        $prefix = Str::before($type, '_path');

        $meta = collect($data[$prefix.'_meta'] ?? [])->first() ?? [];

        if (! isset($data[$type])) {
            if ($category->{$type}) {
                Storage::delete($category->{$type});
            }

            $category->{$type} = null;

            $category->save();

            $this->clearMediaAltText($category, $prefix.'_alt');

            return;
        }

        foreach ($data[$type] as $imageId => $image) {
            $file = $type.'.'.$imageId;

            if (request()->hasFile($file)) {
                if ($category->{$type}) {
                    Storage::delete($category->{$type});
                }

                $encoded = image_manager()->read(request()->file($file))->encodeByExtension('webp');

                $category->{$type} = $this->mediaFileName->resolve(
                    'category/'.$category->id,
                    $meta['file_name'] ?? null,
                    'webp'
                );

                Storage::put($category->{$type}, (string) $encoded);

                $category->save();
            } elseif ($category->{$type}) {
                $renamed = $this->mediaFileName->rename($category->{$type}, $meta['file_name'] ?? null);

                if ($renamed !== $category->{$type}) {
                    $category->{$type} = $renamed;

                    $category->save();
                }
            }
        }

        if (array_key_exists('alt_text', $meta)) {
            $this->saveMediaAltText($category, $prefix.'_alt', $meta['alt_text']);
        }
    }

    /**
     * Save the alt text of a category image, for the requested locale.
     *
     * @param  Category  $category
     */
    protected function saveMediaAltText($category, string $attribute, ?string $altText): void
    {
        foreach (core()->getRequestedLocaleCodes() as $localeCode) {
            if (! $translation = $category->translate($localeCode)) {
                continue;
            }

            $translation->{$attribute} = $altText;
        }

        $category->save();
    }

    /**
     * Drop the alt text of a category image across every locale, used when the image
     * itself is removed.
     *
     * @param  Category  $category
     */
    protected function clearMediaAltText($category, string $attribute): void
    {
        foreach ($category->translations as $translation) {
            $translation->{$attribute} = null;

            $translation->save();
        }
    }

    /**
     * Set same value to all locales in category.
     *
     * To Do: Move column from the `category_translations` to `category` table. And remove
     * this created method.
     *
     * @param  string  $attributeNames
     * @return array
     */
    private function setSameAttributeValueToAllLocale(array $data, ...$attributeNames)
    {
        $requestedLocale = core()->getRequestedLocaleCode();

        $model = app()->make($this->model());

        foreach ($attributeNames as $attributeName) {
            foreach (core()->getAllLocales() as $locale) {
                if ($requestedLocale == $locale->code) {
                    foreach ($model->translatedAttributes as $attribute) {
                        if ($attribute === $attributeName) {
                            $data[$locale->code][$attribute] = $data[$requestedLocale][$attribute] ?? $data[$data['locale']][$attribute];
                        }
                    }
                }
            }
        }

        return $data;
    }
}
