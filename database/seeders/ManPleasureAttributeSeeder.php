<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ManPleasureAttributeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $attributes = $this->attributes();
            $attributeIds = [];

            foreach ($attributes as $position => $definition) {
                [$code, $name, $type, $filterable, $configurable, $options] = $definition;
                $now = now();
                $existing = DB::table('attributes')->where('code', $code)->first();
                $data = [
                    'admin_name' => $name,
                    'type' => $type,
                    'swatch_type' => null,
                    'validation' => null,
                    'position' => $position + 31,
                    'is_required' => 0,
                    'is_unique' => 0,
                    'is_filterable' => $filterable ? 1 : 0,
                    'is_comparable' => 0,
                    'is_configurable' => $configurable ? 1 : 0,
                    'is_user_defined' => 1,
                    'is_visible_on_front' => 1,
                    'value_per_locale' => $type === 'textarea' ? 1 : 0,
                    'value_per_channel' => 0,
                    'enable_wysiwyg' => 0,
                    'updated_at' => $now,
                ];

                if ($existing) {
                    DB::table('attributes')->where('id', $existing->id)->update($data);
                    $attributeIds[$code] = $existing->id;
                } else {
                    $attributeIds[$code] = DB::table('attributes')->insertGetId($data + [
                        'code' => $code,
                        'created_at' => $now,
                    ]);
                }

                DB::table('attribute_translations')->updateOrInsert(
                    ['attribute_id' => $attributeIds[$code], 'locale' => 'en'],
                    ['name' => $name]
                );

                if ($type === 'select') {
                    foreach ($options as $sortOrder => $label) {
                        $option = DB::table('attribute_options')
                            ->where('attribute_id', $attributeIds[$code])
                            ->where('admin_name', $label)
                            ->first();

                        $optionId = $option?->id;

                        if ($optionId) {
                            DB::table('attribute_options')->where('id', $optionId)->update([
                                'sort_order' => $sortOrder + 1,
                            ]);
                        } else {
                            $optionId = DB::table('attribute_options')->insertGetId([
                                'attribute_id' => $attributeIds[$code],
                                'admin_name' => $label,
                                'sort_order' => $sortOrder + 1,
                            ]);
                        }

                        DB::table('attribute_option_translations')->updateOrInsert(
                            ['attribute_option_id' => $optionId, 'locale' => 'en'],
                            ['label' => $label]
                        );
                    }
                }
            }

            foreach (['sku', 'name', 'url_key', 'status', 'visible_individually', 'guest_checkout', 'short_description', 'description', 'price', 'weight', 'manage_stock'] as $code) {
                $attributeIds[$code] = DB::table('attributes')->where('code', $code)->value('id');
            }

            $this->upsertFamily('manpleasure_core', 'ManPleasure Core', [
                'General' => ['sku', 'name', 'url_key', 'status', 'visible_individually', 'guest_checkout', 'mp_product_type', 'mp_material'],
                'Product Details' => ['mp_power_source', 'mp_water_resistance', 'mp_noise_level'],
                'Compatibility & Fit' => ['mp_firmness', 'mp_fit_size', 'mp_compatibility'],
                'Features' => ['mp_rechargeable', 'mp_app_controlled', 'mp_body_safe'],
                'Description' => ['short_description', 'description', 'mp_care_instructions'],
                'Shipping' => ['weight', 'mp_length_cm', 'mp_diameter_cm'],
                'Price' => ['price'],
                'Inventory' => ['manage_stock'],
            ], $attributeIds);

            $this->upsertFamily('manpleasure_consumable', 'ManPleasure Consumable', [
                'General' => ['sku', 'name', 'url_key', 'status', 'visible_individually', 'guest_checkout', 'mp_product_type'],
                'Product Details' => ['mp_volume_ml', 'mp_lubricant_base'],
                'Description' => ['short_description', 'description', 'mp_care_instructions'],
                'Shipping' => ['weight'],
                'Price' => ['price'],
                'Inventory' => ['manage_stock'],
            ], $attributeIds);
        });
    }

    private function upsertFamily(string $code, string $name, array $groups, array $attributeIds): void
    {
        $familyId = DB::table('attribute_families')->where('code', $code)->value('id');

        if ($familyId) {
            DB::table('attribute_families')->where('id', $familyId)->update([
                'name' => $name,
                'status' => 1,
                'is_user_defined' => 1,
            ]);
        } else {
            $familyId = DB::table('attribute_families')->insertGetId([
                'code' => $code,
                'name' => $name,
                'status' => 1,
                'is_user_defined' => 1,
            ]);
        }

        $groupPosition = 0;

        foreach ($groups as $groupName => $attributes) {
            $groupId = DB::table('attribute_groups')
                ->where('attribute_family_id', $familyId)
                ->where('name', $groupName)
                ->value('id');

            if ($groupId) {
                DB::table('attribute_groups')->where('id', $groupId)->update([
                    'position' => $groupPosition + 1,
                    'is_user_defined' => 1,
                ]);
            } else {
                $groupId = DB::table('attribute_groups')->insertGetId([
                    'attribute_family_id' => $familyId,
                    'name' => $groupName,
                    'position' => $groupPosition + 1,
                    'is_user_defined' => 1,
                ]);
            }

            foreach ($attributes as $attributePosition => $code) {
                DB::table('attribute_group_mappings')->updateOrInsert(
                    ['attribute_id' => $attributeIds[$code], 'attribute_group_id' => $groupId],
                    ['position' => $attributePosition + 1]
                );
            }
            $groupPosition++;
        }
    }

    private function attributes(): array
    {
        return [
            ['mp_material', 'Material', 'select', true, false, ['Silicone', 'TPE', 'ABS', 'Glass', 'Metal']],
            ['mp_product_type', 'Product Type', 'select', true, false, ['Device', 'Accessory', 'Lubricant', 'Cleaner', 'Personal Care']],
            ['mp_power_source', 'Power Source', 'select', true, false, ['Manual', 'Battery', 'Rechargeable', 'Mains Powered']],
            ['mp_water_resistance', 'Water Resistance', 'select', true, false, ['Not Rated', 'Splash Resistant', 'Waterproof']],
            ['mp_noise_level', 'Noise Level', 'select', true, false, ['Quiet', 'Moderate', 'Loud']],
            ['mp_firmness', 'Firmness', 'select', true, true, ['Soft', 'Medium', 'Firm']],
            ['mp_fit_size', 'Fit Size', 'select', true, true, ['Small', 'Medium', 'Large']],
            ['mp_variant_color', 'Variant Colour', 'select', true, true, ['Black', 'Blue', 'Red', 'White']],
            ['mp_length_cm', 'Length (cm)', 'text', false, false, []],
            ['mp_diameter_cm', 'Diameter (cm)', 'text', false, false, []],
            ['mp_rechargeable', 'Rechargeable', 'boolean', true, false, []],
            ['mp_app_controlled', 'App Controlled', 'boolean', true, false, []],
            ['mp_body_safe', 'Body-Safe Material', 'boolean', true, false, []],
            ['mp_care_instructions', 'Care Instructions', 'textarea', false, false, []],
            ['mp_compatibility', 'Compatibility', 'textarea', false, false, []],
            ['mp_volume_ml', 'Volume (ml)', 'text', false, false, []],
            ['mp_lubricant_base', 'Lubricant Base', 'select', true, false, ['Water-Based', 'Silicone-Based', 'Hybrid']],
            ['mp_condom_compatible', 'Condom Compatible', 'boolean', true, false, []],
            ['mp_toy_compatible', 'Toy Compatible', 'boolean', true, false, []],
        ];
    }
}
