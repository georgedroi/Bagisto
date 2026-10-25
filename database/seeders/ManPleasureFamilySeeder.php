<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;

class ManPleasureFamilySeeder extends Seeder
{
    /**
     * Seed one attribute family per main category. Run ManPleasureAttributeSeeder first so every field exists.
     *
     * A family that already exists is only added to: missing groups and fields are created, nothing is removed or moved.
     */
    public function run(): void
    {
        $families = app(AttributeFamilyRepository::class);

        $attributes = app(AttributeRepository::class);

        DB::transaction(function () use ($families, $attributes): void {
            foreach ($this->families() as $definition) {
                $groups = $this->groups($definition['details']);

                $this->assertAttributesExist($attributes, $groups);

                $family = $families->findOneByField('code', $definition['code']);

                if ($family) {
                    $this->addMissing($family, $groups, $attributes);

                    continue;
                }

                $families->create([
                    'code' => $definition['code'],
                    'name' => $definition['name'],
                    'attribute_groups' => array_map(fn (array $group) => [
                        'code' => $group['code'],
                        'name' => $group['name'],
                        'column' => $group['column'],
                        'position' => $group['position'],
                        'is_user_defined' => 1,
                        'custom_attributes' => array_map(
                            fn (string $code) => ['code' => $code],
                            $group['attributes']
                        ),
                    ], $groups),
                ]);
            }
        });
    }

    /**
     * Add the groups and fields a family lacks, leaving anything an admin has changed as it is.
     */
    private function addMissing(AttributeFamily $family, array $groups, AttributeRepository $attributes): void
    {
        $assigned = $family->custom_attributes()->pluck('attributes.code')->all();

        foreach ($groups as $definition) {
            $group = $family->attribute_groups()->where('code', $definition['code'])->first()
                ?? $family->attribute_groups()->create([
                    'code' => $definition['code'],
                    'name' => $definition['name'],
                    'column' => $definition['column'],
                    'position' => $definition['position'],
                    'is_user_defined' => 1,
                ]);

            foreach ($definition['attributes'] as $position => $code) {
                if (in_array($code, $assigned, true)) {
                    continue;
                }

                $group->custom_attributes()->attach(
                    $attributes->findOneByField('code', $code)->id,
                    ['position' => $position + 1]
                );

                $assigned[] = $code;
            }
        }
    }

    /**
     * Refuse to seed when a field does not exist yet, so a family is never half built.
     */
    private function assertAttributesExist(AttributeRepository $attributes, array $groups): void
    {
        foreach ($groups as $group) {
            foreach ($group['attributes'] as $code) {
                if (! $attributes->findOneByField('code', $code)) {
                    throw new RuntimeException("Attribute [{$code}] does not exist; run ManPleasureAttributeSeeder first.");
                }
            }
        }
    }

    /**
     * The groups every family shares, with the family's own fields in the details group.
     *
     * @param  array<int, string>  $details
     */
    private function groups(array $details): array
    {
        return [
            [
                'code' => 'general',
                'name' => 'General',
                'column' => 1,
                'position' => 1,
                'attributes' => ['sku', 'name', 'url_key', 'status', 'visible_individually', 'guest_checkout', 'new', 'featured', 'mp_product_type'],
            ],
            [
                'code' => 'product_details',
                'name' => 'Product Details',
                'column' => 1,
                'position' => 2,
                'attributes' => $details,
            ],
            [
                'code' => 'description',
                'name' => 'Description',
                'column' => 1,
                'position' => 3,
                'attributes' => ['short_description', 'description', 'mp_care_instructions'],
            ],
            [
                'code' => 'shipping',
                'name' => 'Shipping',
                'column' => 2,
                'position' => 1,
                'attributes' => ['weight'],
            ],
            [
                'code' => 'price',
                'name' => 'Price',
                'column' => 2,
                'position' => 2,
                'attributes' => ['price', 'special_price', 'special_price_from', 'special_price_to'],
            ],
            [
                'code' => 'inventory',
                'name' => 'Inventory',
                'column' => 2,
                'position' => 3,
                'attributes' => ['manage_stock'],
            ],
        ];
    }

    /**
     * One family per main category and the fields specific to it.
     */
    private function families(): array
    {
        return [
            [
                'code' => 'manpleasure_strokers',
                'name' => 'Strokers & Masturbators',
                'details' => ['mp_material', 'mp_power_source', 'mp_firmness', 'mp_length_cm', 'mp_diameter_cm', 'mp_noise_level', 'mp_water_resistance', 'mp_body_safe'],
            ],
            [
                'code' => 'manpleasure_rings',
                'name' => 'Rings & Delay',
                'details' => ['mp_material', 'mp_fit_size', 'mp_power_source', 'mp_diameter_cm', 'mp_body_safe'],
            ],
            [
                'code' => 'manpleasure_pumps',
                'name' => 'Pumps & Trainers',
                'details' => ['mp_material', 'mp_power_source', 'mp_length_cm', 'mp_diameter_cm', 'mp_noise_level', 'mp_water_resistance'],
            ],
            [
                'code' => 'manpleasure_prostate',
                'name' => 'Prostate & Anal',
                'details' => ['mp_material', 'mp_insertable_length_cm', 'mp_diameter_cm', 'mp_power_source', 'mp_water_resistance', 'mp_noise_level', 'mp_app_controlled', 'mp_body_safe'],
            ],
            [
                'code' => 'manpleasure_condoms',
                'name' => 'Condoms',
                'details' => ['mp_material', 'mp_width_mm', 'mp_pack_size', 'mp_texture', 'mp_lubricated', 'mp_best_before'],
            ],
            [
                'code' => 'manpleasure_lubricants',
                'name' => 'Lubricants & Care',
                'details' => ['mp_volume_ml', 'mp_lubricant_base', 'mp_condom_compatible', 'mp_toy_compatible', 'mp_best_before'],
            ],
            [
                'code' => 'manpleasure_accessories',
                'name' => 'Accessories & Storage',
                'details' => ['mp_material', 'mp_compatibility', 'mp_power_source', 'mp_length_cm', 'mp_diameter_cm'],
            ],
            [
                'code' => 'manpleasure_apparel',
                'name' => 'Underwear & Costumes',
                'details' => ['size', 'color', 'mp_fabric', 'mp_audience'],
            ],
        ];
    }
}
