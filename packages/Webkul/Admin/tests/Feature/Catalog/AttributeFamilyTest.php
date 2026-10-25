<?php

use Webkul\Attribute\Models\Attribute as AttributeModel;
use Webkul\Attribute\Models\AttributeFamily as AttributeFamilyModel;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/**
 * Build the group inputs the family edit form submits for the family as it is stored.
 */
function familyGroupInputs(AttributeFamilyModel $family): array
{
    return $family->attribute_groups()->with('custom_attributes')->get()
        ->mapWithKeys(fn ($group) => [
            $group->id => [
                'code' => $group->code,
                'name' => $group->name,
                'column' => $group->column,
                'position' => $group->position,
                'custom_attributes' => $group->custom_attributes
                    ->map(fn ($attribute) => ['id' => $attribute->id, 'position' => $attribute->pivot->position])
                    ->values()
                    ->all(),
            ],
        ])
        ->all();
}

it('should return attribute family listing page', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.catalog.families.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.index.title'))
        ->assertSeeText(trans('admin::app.catalog.families.index.add'));
});

it('should return listing items of attribute family', function () {
    // Arrange.
    $attributeFamily = AttributeFamilyModel::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    getJson(route('admin.catalog.families.index'), [
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->assertOk()
        ->assertJsonPath('records.0.id', $attributeFamily->id)
        ->assertJsonPath('records.0.code', $attributeFamily->code)
        ->assertJsonPath('meta.total', 2);
});

it('should return attribute family create page', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.catalog.families.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.create.title'))
        ->assertSeeText(trans('admin::app.catalog.families.create.back-btn'));
});

it('should store newly created attribute family', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.catalog.families.store'), [
        'code' => $code = fake()->numerify('code########'),
        'name' => $name = fake()->name(),
        'attribute_groups' => [
            [
                'code' => $code,
                'name' => $name,
                'column' => 1,
            ],
        ],
    ])
        ->assertRedirectToRoute('admin.catalog.families.index')
        ->isRedirection();

    $this->assertModelWise([
        AttributeFamilyModel::class => [
            [
                'code' => $code,
                'name' => $name,
            ],
        ],
    ]);
});

it('should fail the validation with errors when certain inputs are not provided when store in attribute family', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.catalog.families.store'))
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('code')
        ->assertUnprocessable();
});

it('should return edit page of attribute families', function () {
    // Arrange.
    $attributeFamily = AttributeFamilyModel::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.catalog.families.edit', $attributeFamily->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.edit.title'))
        ->assertSeeText(trans('admin::app.catalog.families.edit.back-btn'));
});

it('should fail the validation with errors when certain inputs are not provided when update in attribute family', function () {
    // Arrange.
    $attributeFamily = AttributeFamilyModel::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.catalog.families.update', $attributeFamily->id))
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('code')
        ->assertUnprocessable();
});

it('should update the existing attribute families', function () {
    // Arrange.
    $attributeFamily = AttributeFamilyModel::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.catalog.families.update', $attributeFamily->id), [
        'code' => $attributeFamily->code,
        'name' => $name = fake()->name(),
    ])
        ->assertRedirectToRoute('admin.catalog.families.index')
        ->isRedirection();

    $this->assertModelWise([
        AttributeFamilyModel::class => [
            [
                'id' => $attributeFamily->id,
                'code' => $attributeFamily->code,
                'name' => $name,
            ],
        ],
    ]);
});

it('should delete the existing attribute family', function () {
    // Arrange.
    $attributeFamily = AttributeFamilyModel::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    deleteJson(route('admin.catalog.families.delete', $attributeFamily->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.delete-success'));

    $this->assertDatabaseMissing('attribute_families', [
        'id' => $attributeFamily->id,
    ]);
});

it('should keep the code of an attribute family unchanged on update', function () {
    // Arrange.
    $attributeFamily = AttributeFamilyModel::factory()->create();

    $defaultFamily = AttributeFamilyModel::query()
        ->where('code', AttributeFamilyModel::DEFAULT_CODE)
        ->firstOrFail();

    // Act and Assert.
    $this->loginAsAdmin();

    foreach ([$attributeFamily, $defaultFamily] as $family) {
        putJson(route('admin.catalog.families.update', $family->id), [
            'code' => 'submitted_code_'.$family->id,
            'name' => $family->name,
            'attribute_groups' => familyGroupInputs($family),
        ])
            ->assertRedirectToRoute('admin.catalog.families.index')
            ->isRedirection();

        $this->assertDatabaseHas('attribute_families', [
            'id' => $family->id,
            'code' => $family->code,
        ]);
    }
});

it('should refuse to delete the default attribute family', function () {
    // Arrange.
    $attributeFamily = AttributeFamilyModel::query()
        ->where('code', AttributeFamilyModel::DEFAULT_CODE)
        ->firstOrFail();

    // Act and Assert.
    $this->loginAsAdmin();

    deleteJson(route('admin.catalog.families.delete', $attributeFamily->id))
        ->assertStatus(400)
        ->assertJsonPath('message', trans('admin::app.catalog.families.default-delete-error'));

    $this->assertDatabaseHas('attribute_families', ['id' => $attributeFamily->id]);
});

it('should still open the create family screen when the default family is missing', function () {
    // Arrange.
    AttributeFamilyModel::factory()->create();

    AttributeFamilyModel::query()->where('code', AttributeFamilyModel::DEFAULT_CODE)->delete();

    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.catalog.families.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.create.title'));
});

it('should refuse to save a family that would leave system attributes without a group', function () {
    // Arrange.
    $defaultFamily = AttributeFamilyModel::query()
        ->where('code', AttributeFamilyModel::DEFAULT_CODE)
        ->firstOrFail();

    $groupIds = $defaultFamily->attribute_groups()->pluck('id')->all();

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.catalog.families.update', $defaultFamily->id), [
        'code' => $defaultFamily->code,
        'name' => $defaultFamily->name,
    ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($defaultFamily->attribute_groups()->pluck('id')->all())->toBe($groupIds);
});

it('should delete a group that only holds user defined attributes and keep the attributes', function () {
    // Arrange.
    $family = AttributeFamilyModel::factory()->create();

    $keptGroup = $family->attribute_groups()->create(['code' => 'kept', 'name' => 'Kept', 'column' => 1, 'position' => 1, 'is_user_defined' => 1]);

    $removedGroup = $family->attribute_groups()->create(['code' => 'removed', 'name' => 'Removed', 'column' => 1, 'position' => 2, 'is_user_defined' => 1]);

    $attribute = AttributeModel::factory()->create(['is_user_defined' => 1]);

    $removedGroup->custom_attributes()->attach($attribute->id, ['position' => 1]);

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.catalog.families.update', $family->id), [
        'code' => $family->code,
        'name' => $family->name,
        'attribute_groups' => [
            $keptGroup->id => ['code' => 'kept', 'name' => 'Kept', 'column' => 1, 'position' => 1],
        ],
    ])
        ->assertRedirectToRoute('admin.catalog.families.index');

    $this->assertDatabaseMissing('attribute_groups', ['id' => $removedGroup->id]);

    $this->assertDatabaseMissing('attribute_group_mappings', ['attribute_group_id' => $removedGroup->id]);

    $this->assertDatabaseHas('attribute_groups', ['id' => $keptGroup->id]);

    $this->assertDatabaseHas('attributes', ['id' => $attribute->id]);
});

it('should delete a group holding a system attribute when the attribute moves to another group', function () {
    // Arrange.
    $family = AttributeFamilyModel::factory()->create();

    $keptGroup = $family->attribute_groups()->create(['code' => 'kept', 'name' => 'Kept', 'column' => 1, 'position' => 1, 'is_user_defined' => 1]);

    $removedGroup = $family->attribute_groups()->create(['code' => 'removed', 'name' => 'Removed', 'column' => 1, 'position' => 2, 'is_user_defined' => 1]);

    $systemAttribute = AttributeModel::factory()->create(['is_user_defined' => 0]);

    $removedGroup->custom_attributes()->attach($systemAttribute->id, ['position' => 1]);

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.catalog.families.update', $family->id), [
        'code' => $family->code,
        'name' => $family->name,
        'attribute_groups' => [
            $keptGroup->id => [
                'code' => 'kept',
                'name' => 'Kept',
                'column' => 1,
                'position' => 1,
                'custom_attributes' => [['id' => $systemAttribute->id, 'position' => 1]],
            ],
        ],
    ])
        ->assertRedirectToRoute('admin.catalog.families.index');

    $this->assertDatabaseMissing('attribute_groups', ['id' => $removedGroup->id]);

    $this->assertDatabaseHas('attribute_group_mappings', [
        'attribute_group_id' => $keptGroup->id,
        'attribute_id' => $systemAttribute->id,
    ]);
});

it('should roll the whole family update back when it fails halfway', function () {
    // Arrange.
    $family = AttributeFamilyModel::factory()->create(['name' => 'Original']);

    $keptGroup = $family->attribute_groups()->create(['code' => 'kept', 'name' => 'Kept', 'column' => 1, 'position' => 1, 'is_user_defined' => 1]);

    $removedGroup = $family->attribute_groups()->create(['code' => 'removed', 'name' => 'Removed', 'column' => 1, 'position' => 2, 'is_user_defined' => 1]);

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.catalog.families.update', $family->id), [
        'code' => $family->code,
        'name' => 'Changed',
        'attribute_groups' => [
            $keptGroup->id => [
                'code' => 'kept',
                'name' => 'Renamed',
                'column' => 1,
                'position' => 1,
                'custom_attributes' => [['id' => 999999, 'position' => 1]],
            ],
        ],
    ])
        ->assertServerError();

    $this->assertDatabaseHas('attribute_families', ['id' => $family->id, 'name' => 'Original']);

    $this->assertDatabaseHas('attribute_groups', ['id' => $keptGroup->id, 'name' => 'Kept']);

    $this->assertDatabaseHas('attribute_groups', ['id' => $removedGroup->id]);
});

it('should show how many products are affected on the family edit page', function () {
    // Arrange.
    $family = AttributeFamilyModel::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.catalog.families.edit', $family->id))
        ->assertOk()
        ->assertSee('productCount: 0', false)
        ->assertSee(trans('admin::app.catalog.families.edit.delete-group-warning'), false);
});
