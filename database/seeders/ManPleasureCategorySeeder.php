<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\Category;

class ManPleasureCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $root = Category::whereNull('parent_id')
                ->whereHas('translations', fn ($query) => $query->where('slug', 'root'))
                ->firstOrFail();

            foreach ($this->categories() as $position => $definition) {
                $parent = $this->upsertCategory($root, $definition, $position + 1);

                foreach ($definition['children'] as $childPosition => $child) {
                    $this->upsertCategory($parent, $child, $childPosition + 1);
                }
            }
        });
    }

    private function upsertCategory(Category $parent, array $definition, int $position): Category
    {
        $category = Category::whereHas('translations', function ($query) use ($definition) {
            $query->where('locale', 'en')->where('slug', $definition['slug']);
        })->first();

        if (! $category) {
            $category = $parent->children()->create([
                'position' => $position,
                'status' => 1,
                'display_mode' => 'products_and_description',
            ]);
        } else {
            $category->position = $position;
            $category->status = 1;
            $category->display_mode = 'products_and_description';
            $category->save();
        }

        $translation = $category->translateOrNew('en');
        $translation->name = $definition['name'];
        $translation->slug = $definition['slug'];
        $translation->description = $definition['description'];
        $translation->meta_title = $definition['name'].' | ManPleasure';
        $translation->meta_description = $definition['description'];
        $translation->meta_keywords = '';
        $translation->save();

        return $category->fresh();
    }

    private function categories(): array
    {
        return [
            $this->category('Strokers & Masturbators', 'strokers-masturbators', [
                ['Manual Strokers', 'manual-strokers'],
                ['Automatic Strokers', 'automatic-strokers'],
                ['Sleeves', 'sleeves'],
                ['Compact & Travel', 'compact-travel'],
            ]),
            $this->category('Rings & Enhancers', 'rings-enhancers', [
                ['Cock Rings', 'cock-rings'],
                ['Vibrating Rings', 'vibrating-rings'],
                ['Extension & Enhancement', 'extension-enhancement'],
            ]),
            $this->category('Pumps & Trainers', 'pumps-trainers', [
                ['Manual Pumps', 'manual-pumps'],
                ['Electric Pumps', 'electric-pumps'],
                ['Training & Support', 'training-support'],
            ]),
            $this->category('Prostate & Anal Wellness', 'prostate-anal-wellness', [
                ['Prostate Massagers', 'prostate-massagers'],
                ['Anal Toys', 'anal-toys'],
                ['Beginner-Friendly', 'beginner-friendly'],
            ]),
            $this->category('Lubricants & Care', 'lubricants-care', [
                ['Water-Based Lubricants', 'water-based-lubricants'],
                ['Silicone-Based Lubricants', 'silicone-based-lubricants'],
                ['Toy Cleaners', 'toy-cleaners'],
                ['Personal Care', 'personal-care'],
            ]),
            $this->category('Accessories & Storage', 'accessories-storage', [
                ['Storage', 'storage'],
                ['Replacement Parts', 'replacement-parts'],
                ['Chargers & Cables', 'chargers-cables'],
                ['Travel Accessories', 'travel-accessories'],
            ]),
        ];
    }

    private function category(string $name, string $slug, array $children): array
    {
        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $name.' products for a discreet, premium wellness experience.',
            'children' => array_map(fn (array $child) => [
                'name' => $child[0],
                'slug' => $child[1],
                'description' => $child[0].' from the '.$name.' collection.',
            ], $children),
        ];
    }
}
