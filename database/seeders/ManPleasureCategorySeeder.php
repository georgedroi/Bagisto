<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Webkul\Category\Models\Category;
use Webkul\Category\Repositories\CategoryRepository;

class ManPleasureCategorySeeder extends Seeder
{
    /**
     * Seed the category tree, renaming earlier categories in place and retiring the ones that were merged.
     */
    public function run(): void
    {
        $repository = app(CategoryRepository::class);

        $definitions = $this->categories();

        $this->assertNamesAreUnique($definitions);

        DB::transaction(function () use ($repository, $definitions): void {
            $root = $repository->findBySlugOrFail('root');

            foreach ($definitions as $position => $definition) {
                $parent = $this->upsertCategory($repository, $root, $definition, $position + 1);

                foreach ($definition['children'] as $childPosition => $child) {
                    $this->upsertCategory($repository, $parent, $child, $childPosition + 1);
                }
            }

            foreach ($this->retired() as $slug => $mergeInto) {
                $this->retireCategory($repository, $slug, $mergeInto);
            }
        });
    }

    /**
     * Create the category, or update the one that already has the slug or is being renamed from an earlier slug.
     */
    private function upsertCategory(CategoryRepository $repository, Category $parent, array $definition, int $position): Category
    {
        $category = $repository->findBySlug($definition['slug'])
            ?? (isset($definition['replaces']) ? $repository->findBySlug($definition['replaces']) : null);

        if (! $category) {
            $this->assertNameIsFree($repository, $definition['name']);
        }

        $data = [
            'parent_id' => $parent->id,
            'position' => $position,
            'status' => 1,
            'display_mode' => 'products_and_description',
            'en' => [
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'description' => $definition['description'],
                'meta_title' => $definition['name'].' | ManPleasure',
                'meta_description' => $definition['description'],
                'meta_keywords' => '',
            ],
        ];

        $category = $category
            ? $repository->update($data, $category->id)
            : $repository->create($data);

        return $category->fresh();
    }

    /**
     * Remove a merged category after moving its products into the category that replaces it.
     */
    private function retireCategory(CategoryRepository $repository, string $slug, ?string $mergeInto): void
    {
        $category = $repository->findBySlug($slug);

        if (! $category) {
            return;
        }

        if ($category->children()->exists()) {
            throw new RuntimeException("Category [{$slug}] still has sub-categories; move them before retiring it.");
        }

        $productIds = $category->products()->pluck('products.id')->all();

        if ($productIds) {
            if ($mergeInto === null) {
                throw new RuntimeException('Category ['.$slug.'] still holds '.count($productIds).' product(s) and has no replacement; move them first.');
            }

            $repository->findBySlugOrFail($mergeInto)->products()->syncWithoutDetaching($productIds);
        }

        $repository->delete($category->id);
    }

    /**
     * Refuse to create a category whose name is already taken, because the product importer matches categories by name.
     */
    private function assertNameIsFree(CategoryRepository $repository, string $name): void
    {
        if ($repository->whereTranslation('name', $name)->exists()) {
            throw new RuntimeException("A category named [{$name}] already exists; category names must be unique.");
        }
    }

    /**
     * Refuse a tree in which two categories share a name or a slug.
     */
    private function assertNamesAreUnique(array $definitions): void
    {
        $names = [];

        $slugs = [];

        foreach ($definitions as $definition) {
            foreach ([$definition, ...$definition['children']] as $node) {
                $names[] = Str::lower($node['name']);

                $slugs[] = $node['slug'];
            }
        }

        if (count($names) !== count(array_unique($names)) || count($slugs) !== count(array_unique($slugs))) {
            throw new RuntimeException('The category tree has a repeated name or slug.');
        }
    }

    /**
     * Categories removed from the tree, each with the slug that takes over its products (null when none does).
     *
     * @return array<string, string|null>
     */
    private function retired(): array
    {
        return [
            'compact-travel' => 'sleeves-compact',
            'vibrating-rings' => 'cock-rings',
            'beginner-friendly' => null,
            'personal-care' => 'cleaners-care',
            'travel-accessories' => 'storage-travel',
        ];
    }

    private function categories(): array
    {
        return [
            $this->category('Strokers & Masturbators', 'strokers-masturbators', null, [
                ['Manual Strokers', 'manual-strokers'],
                ['Automatic Strokers', 'automatic-strokers'],
                ['Sleeves & Compact', 'sleeves-compact', 'sleeves'],
            ]),
            $this->category('Rings & Delay', 'rings-delay', 'rings-enhancers', [
                ['Cock Rings', 'cock-rings'],
                ['Extenders', 'extenders', 'extension-enhancement'],
                ['Delay Sprays', 'delay-sprays'],
            ]),
            $this->category('Pumps & Trainers', 'pumps-trainers', null, [
                ['Manual Pumps', 'manual-pumps'],
                ['Electric Pumps', 'electric-pumps'],
                ['Trainers & Accessories', 'trainers-accessories', 'training-support'],
            ]),
            $this->category('Prostate & Anal', 'prostate-anal', 'prostate-anal-wellness', [
                ['Prostate Massagers', 'prostate-massagers'],
                ['Anal Toys', 'anal-toys'],
            ]),
            $this->category('Condoms', 'condoms', null, [
                ['Regular Condoms', 'regular-condoms'],
                ['Thin Condoms', 'thin-condoms'],
                ['Lasting Condoms', 'lasting-condoms'],
            ]),
            $this->category('Lubricants & Care', 'lubricants-care', null, [
                ['Water-Based Lubricants', 'water-based-lubricants'],
                ['Silicone-Based Lubricants', 'silicone-based-lubricants'],
                ['Cleaners & Care', 'cleaners-care', 'toy-cleaners'],
            ]),
            $this->category('Accessories & Storage', 'accessories-storage', null, [
                ['Storage & Travel', 'storage-travel', 'storage'],
                ['Replacement Parts', 'replacement-parts'],
                ['Chargers & Cables', 'chargers-cables'],
            ]),
            $this->category('Underwear & Costumes', 'underwear-costumes', null, [
                ["Men's Underwear", 'mens-underwear'],
                ["Women's Lingerie", 'womens-lingerie'],
                ['Costumes & Cosplay', 'costumes-cosplay'],
            ]),
        ];
    }

    private function category(string $name, string $slug, ?string $replaces, array $children): array
    {
        return [
            'name' => $name,
            'slug' => $slug,
            'replaces' => $replaces,
            'description' => $name.' products for a discreet, premium wellness experience.',
            'children' => array_map(fn (array $child) => [
                'name' => $child[0],
                'slug' => $child[1],
                'replaces' => $child[2] ?? null,
                'description' => $child[0].' from the '.$name.' collection.',
            ], $children),
        ];
    }
}
