<?php

use Database\Seeders\ManPleasureAttributeSeeder;
use Database\Seeders\ManPleasureCategorySeeder;
use Database\Seeders\ManPleasureFamilySeeder;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily as AttributeFamilyModel;
use Webkul\Category\Models\Category as CategoryModel;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Models\Product as ProductModel;

/**
 * Create a category the way an earlier version of the tree held it.
 */
function earlierCategory(string $name, string $slug, int $parentId): CategoryModel
{
    return app(CategoryRepository::class)->create([
        'parent_id' => $parentId,
        'position' => 1,
        'status' => 1,
        'display_mode' => 'products_and_description',
        'en' => [
            'name' => $name,
            'slug' => $slug,
            'description' => $name,
            'meta_title' => $name,
            'meta_description' => $name,
            'meta_keywords' => '',
        ],
    ]);
}

/**
 * Read the tree under the root as main category name => sub-category names.
 */
function categoryTreeUnderRoot(): array
{
    $rootId = app(CategoryRepository::class)->findBySlugOrFail('root')->id;

    return CategoryModel::query()
        ->where('parent_id', $rootId)
        ->orderBy('position')
        ->get()
        ->mapWithKeys(fn ($main) => [
            $main->name => CategoryModel::query()
                ->where('parent_id', $main->id)
                ->orderBy('position')
                ->get()
                ->pluck('name')
                ->all(),
        ])
        ->all();
}

it('should build the approved category tree with at most three sub-categories each', function () {
    // Act.
    app(ManPleasureCategorySeeder::class)->run();

    // Assert.
    $tree = categoryTreeUnderRoot();

    expect($tree)->toBe([
        'Strokers & Masturbators' => ['Manual Strokers', 'Automatic Strokers', 'Sleeves & Compact'],
        'Rings & Delay' => ['Cock Rings', 'Extenders', 'Delay Sprays'],
        'Pumps & Trainers' => ['Manual Pumps', 'Electric Pumps', 'Trainers & Accessories'],
        'Prostate & Anal' => ['Prostate Massagers', 'Anal Toys'],
        'Condoms' => ['Regular Condoms', 'Thin Condoms', 'Lasting Condoms'],
        'Lubricants & Care' => ['Water-Based Lubricants', 'Silicone-Based Lubricants', 'Cleaners & Care'],
        'Accessories & Storage' => ['Storage & Travel', 'Replacement Parts', 'Chargers & Cables'],
        'Underwear & Costumes' => ["Men's Underwear", "Women's Lingerie", 'Costumes & Cosplay'],
    ]);

    $names = collect($tree)->flatMap(fn ($children, $main) => [$main, ...$children])->map(fn ($name) => strtolower($name));

    expect($names->unique()->count())->toBe($names->count());
});

it('should leave the category tree unchanged when the seeder runs again', function () {
    // Arrange.
    app(ManPleasureCategorySeeder::class)->run();

    $count = CategoryModel::query()->count();

    // Act.
    app(ManPleasureCategorySeeder::class)->run();

    // Assert.
    expect(CategoryModel::query()->count())->toBe($count);
});

it('should rename earlier categories in place so their products stay linked', function () {
    // Arrange.
    $rootId = app(CategoryRepository::class)->findBySlugOrFail('root')->id;

    $main = earlierCategory('Rings & Enhancers', 'rings-enhancers', $rootId);

    $child = earlierCategory('Extension & Enhancement', 'extension-enhancement', $main->id);

    $product = ProductModel::factory()->create();

    $child->products()->attach($product->id);

    // Act.
    app(ManPleasureCategorySeeder::class)->run();

    // Assert.
    expect($main->fresh()->name)->toBe('Rings & Delay')
        ->and($child->fresh()->name)->toBe('Extenders')
        ->and($child->fresh()->parent_id)->toBe($main->id);

    $this->assertDatabaseHas('product_categories', [
        'product_id' => $product->id,
        'category_id' => $child->id,
    ]);
});

it('should move the products of a merged category into its replacement', function () {
    // Arrange.
    $rootId = app(CategoryRepository::class)->findBySlugOrFail('root')->id;

    $main = earlierCategory('Rings & Enhancers', 'rings-enhancers', $rootId);

    $rings = earlierCategory('Cock Rings', 'cock-rings', $main->id);

    $vibrating = earlierCategory('Vibrating Rings', 'vibrating-rings', $main->id);

    $product = ProductModel::factory()->create();

    $vibrating->products()->attach($product->id);

    // Act.
    app(ManPleasureCategorySeeder::class)->run();

    // Assert.
    $this->assertDatabaseMissing('categories', ['id' => $vibrating->id]);

    $this->assertDatabaseHas('product_categories', [
        'product_id' => $product->id,
        'category_id' => $rings->id,
    ]);
});

it('should refuse to retire a category that holds products and has no replacement', function () {
    // Arrange.
    $rootId = app(CategoryRepository::class)->findBySlugOrFail('root')->id;

    $main = earlierCategory('Prostate & Anal Wellness', 'prostate-anal-wellness', $rootId);

    $beginner = earlierCategory('Beginner-Friendly', 'beginner-friendly', $main->id);

    $product = ProductModel::factory()->create();

    $beginner->products()->attach($product->id);

    // Act and Assert.
    expect(fn () => app(ManPleasureCategorySeeder::class)->run())->toThrow(RuntimeException::class);

    $this->assertDatabaseHas('categories', ['id' => $beginner->id]);

    $this->assertDatabaseHas('product_categories', [
        'product_id' => $product->id,
        'category_id' => $beginner->id,
    ]);

    expect(app(CategoryRepository::class)->findBySlug('condoms'))->toBeNull();
});

it('should refuse to create a category whose name another category already uses', function () {
    // Arrange.
    $rootId = app(CategoryRepository::class)->findBySlugOrFail('root')->id;

    earlierCategory('Condoms', 'condoms-by-hand', $rootId);

    // Act and Assert.
    expect(fn () => app(ManPleasureCategorySeeder::class)->run())->toThrow(RuntimeException::class);
});

it('should give both product families the new, featured and special price fields and a code on every group', function () {
    // Act.
    app(ManPleasureAttributeSeeder::class)->run();

    $consumable = AttributeFamilyModel::query()->where('code', 'manpleasure_consumable')->firstOrFail();

    $consumable->attribute_groups()->create(['name' => 'Ingredients & Care', 'column' => 1, 'position' => 9, 'is_user_defined' => 1]);

    app(ManPleasureAttributeSeeder::class)->run();

    // Assert.
    foreach (['manpleasure_core', 'manpleasure_consumable'] as $code) {
        $family = AttributeFamilyModel::query()->where('code', $code)->firstOrFail();

        $assigned = $family->custom_attributes()->pluck('attributes.code')->all();

        expect($assigned)->toContain('new', 'featured', 'special_price', 'special_price_from', 'special_price_to');

        expect($family->attribute_groups()->whereNull('code')->count())->toBe(0);
    }

    expect(DB::table('attribute_groups')->where('attribute_family_id', $consumable->id)->where('name', 'Ingredients & Care')->value('code'))
        ->toBe('ingredients_care');
});

it('should create one family per main category with the approved fields', function () {
    // Act.
    app(ManPleasureAttributeSeeder::class)->run();

    app(ManPleasureFamilySeeder::class)->run();

    // Assert.
    $expected = [
        'manpleasure_strokers' => ['mp_material', 'mp_power_source', 'mp_firmness'],
        'manpleasure_rings' => ['mp_material', 'mp_fit_size', 'mp_power_source'],
        'manpleasure_pumps' => ['mp_material', 'mp_power_source', 'mp_length_cm'],
        'manpleasure_prostate' => ['mp_material', 'mp_insertable_length_cm', 'mp_app_controlled'],
        'manpleasure_condoms' => ['mp_material', 'mp_width_mm', 'mp_pack_size', 'mp_texture', 'mp_lubricated', 'mp_best_before'],
        'manpleasure_lubricants' => ['mp_volume_ml', 'mp_lubricant_base', 'mp_condom_compatible', 'mp_toy_compatible'],
        'manpleasure_accessories' => ['mp_material', 'mp_compatibility', 'mp_power_source'],
        'manpleasure_apparel' => ['size', 'color', 'mp_fabric', 'mp_audience'],
    ];

    foreach ($expected as $code => $fields) {
        $family = AttributeFamilyModel::query()->where('code', $code)->firstOrFail();

        $assigned = $family->custom_attributes()->pluck('attributes.code')->all();

        expect($assigned)->toContain('sku', 'name', 'url_key', 'price', 'special_price', 'special_price_from', 'special_price_to', 'cost', 'new', 'featured', 'weight', 'manage_stock', ...$fields);

        expect($family->attribute_groups()->whereNull('code')->count())->toBe(0);
    }

    foreach (['mp_volume_ml', 'mp_width_mm', 'mp_pack_size', 'mp_fabric'] as $code) {
        expect((int) DB::table('attributes')->where('code', $code)->value('is_required'))->toBe(1);
    }

    foreach (['mp_material', 'mp_compatibility', 'mp_power_source'] as $code) {
        expect((int) DB::table('attributes')->where('code', $code)->value('is_required'))->toBe(0);
    }
});

it('should only add to an existing family and never move or remove what an admin changed', function () {
    // Arrange.
    app(ManPleasureAttributeSeeder::class)->run();

    app(ManPleasureFamilySeeder::class)->run();

    $family = AttributeFamilyModel::query()->where('code', 'manpleasure_condoms')->firstOrFail();

    $custom = $family->attribute_groups()->create(['code' => 'size_guide', 'name' => 'Size Guide', 'column' => 1, 'position' => 9, 'is_user_defined' => 1]);

    $textureId = DB::table('attributes')->where('code', 'mp_texture')->value('id');

    $widthId = DB::table('attributes')->where('code', 'mp_width_mm')->value('id');

    DB::table('attribute_group_mappings')->where('attribute_id', $textureId)->delete();

    DB::table('attribute_group_mappings')->where('attribute_id', $widthId)->update(['attribute_group_id' => $custom->id]);

    // Act.
    app(ManPleasureFamilySeeder::class)->run();

    // Assert.
    expect($family->attribute_groups()->where('code', 'size_guide')->count())->toBe(1);

    $this->assertDatabaseHas('attribute_group_mappings', ['attribute_id' => $widthId, 'attribute_group_id' => $custom->id]);

    expect(DB::table('attribute_group_mappings')->where('attribute_id', $widthId)->count())->toBe(1);

    $this->assertDatabaseHas('attribute_group_mappings', ['attribute_id' => $textureId]);
});

it('should build no family at all when a field is missing', function () {
    // Arrange.
    app(ManPleasureAttributeSeeder::class)->run();

    DB::table('attributes')->where('code', 'mp_fabric')->delete();

    // Act and Assert.
    expect(fn () => app(ManPleasureFamilySeeder::class)->run())->toThrow(RuntimeException::class);

    expect(AttributeFamilyModel::query()->where('code', 'manpleasure_strokers')->exists())->toBeFalse();
});
