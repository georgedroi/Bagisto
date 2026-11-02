<?php

namespace App\Http\Requests;

use App\Support\ProductDescriptionSanitizer;
use App\Support\ProductEntryCatalog;
use App\Support\ProductVariations;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Webkul\Admin\Validations\ProductCategoryUniqueSlug;
use Webkul\Core\Rules\Decimal;
use Webkul\Core\Rules\Slug;

class ProductEntryRequest extends FormRequest
{
    /**
     * Determine if the admin may add a product; the route's permission has already decided.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Clean the description, which the storefront prints as HTML, and unpack the variations,
     * sent as one JSON field because a large list would pass the server's input limit.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('description')) {
            $this->merge(['description' => app(ProductDescriptionSanitizer::class)->clean((string) $this->input('description'))]);
        }

        $data = json_decode((string) $this->input('variation_data'), true);

        if (is_array($data) && ! empty($data['variations'])) {
            $this->merge([
                'variations' => $data['variations'],
                'variation_rows' => $data['rows'] ?? [],
            ]);
        }
    }

    /**
     * Get the validation rules, including the rules for the fields of the category's family.
     */
    public function rules(): array
    {
        $catalog = app(ProductEntryCatalog::class);

        $productId = $this->route('id');

        $rules = [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', Rule::unique('products', 'sku')->ignore($productId), new Slug],
            'url_key' => ['required', new ProductCategoryUniqueSlug('products', $productId)],
            'price' => ['required', new Decimal, 'numeric', 'gt:0'],
            'special_price' => ['nullable', new Decimal, 'numeric', 'gt:0', 'lt:price'],
            'cost' => ['nullable', new Decimal, 'numeric', 'min:0'],
            'special_price_from' => ['nullable', 'date'],
            'special_price_to' => ['nullable', 'date', 'after_or_equal:special_price_from'],
            'stock' => ['required', 'integer', 'min:0'],
            'weight' => ['required', new Decimal, 'numeric', 'gt:0'],
            'short_description' => ['required', 'string', 'min:20', 'max:255'],
            'description' => ['required', 'string', 'min:60'],
            'status' => ['required', 'in:0,1'],
            'draft' => ['sometimes', 'boolean'],
            'new' => ['sometimes', 'boolean'],
            'featured' => ['sometimes', 'boolean'],
            'mp_product_type' => ['required', Rule::in(array_column($catalog->productTypes(), 'value'))],
            'images.files.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        if ($this->filled('variations')) {
            $rules = array_merge(
                Arr::except($rules, ['price', 'special_price', 'cost', 'special_price_from', 'special_price_to', 'stock']),
                $this->variationRules()
            );
        }

        $resolved = $catalog->resolve((int) $this->input('category_id'));

        $family = $resolved ? ($catalog->families()[$resolved['family']] ?? null) : null;

        foreach ($family['fields'] ?? [] as $field) {
            $rules[$field['code']] = $this->rulesForField($field);
        }

        return $rules;
    }

    /**
     * Add the category check, which needs the family to exist.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('category_id')) {
                return;
            }

            if (! app(ProductEntryCatalog::class)->resolve((int) $this->input('category_id'))) {
                $validator->errors()->add('category_id', trans('admin::product-entry.no-family'));
            }

            if ($this->filled('variations') && $validator->errors()->isEmpty()) {
                $this->checkVariations($validator);
            }
        });
    }

    /**
     * Rules for the variations and their rows.
     */
    protected function variationRules(): array
    {
        $spaces = 'regex:/^\S(.*\S)?$/u';

        return [
            'variations' => ['array', 'max:2'],
            'variations.*.name' => ['required', 'string', 'max:40', $spaces],
            'variations.*.options' => ['required', 'array', 'min:1', 'max:20'],
            'variations.*.options.*.label' => ['required', 'string', 'max:60', $spaces],
            'variations.*.options.*.note' => ['nullable', 'string', 'max:120'],
            'variations.*.options.*.keep_image' => ['nullable', 'boolean'],
            'variation_rows' => ['required', 'array'],
            'variation_rows.*.options' => ['required', 'array'],
            'variation_rows.*.options.*' => ['integer', 'min:0'],
            'variation_rows.*.price' => ['required', new Decimal, 'numeric', 'gt:0'],
            'variation_rows.*.special_price' => ['nullable', new Decimal, 'numeric', 'gt:0', 'lt:variation_rows.*.price'],
            'variation_rows.*.special_price_from' => ['nullable', 'date'],
            'variation_rows.*.special_price_to' => ['nullable', 'date', 'after_or_equal:variation_rows.*.special_price_from'],
            'variation_rows.*.stock' => ['required', 'integer', 'min:0'],
            'variation_rows.*.sku' => ['nullable', 'string', 'max:200'],
            'variation_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * Refuse repeated names, a list that does not cover every combination once, and SKUs that are invalid or taken.
     */
    protected function checkVariations(Validator $validator): void
    {
        $variations = $this->input('variations');

        $rows = $this->input('variation_rows');

        $helper = app(ProductVariations::class);

        $names = array_map(fn (array $variation) => mb_strtolower($variation['name']), $variations);

        if (count($names) !== count(array_unique($names))) {
            $validator->errors()->add('variations.1.name', trans('admin::seller-workspace.add.variation.same-name'));
        }

        foreach ($variations as $index => $variation) {
            $seen = [];

            foreach ($variation['options'] as $position => $option) {
                $label = mb_strtolower($option['label']);

                if (in_array($label, $seen, true)) {
                    $validator->errors()->add("variations.{$index}.options.{$position}.label", trans('admin::seller-workspace.add.variation.same-option'));
                }

                $seen[] = $label;
            }
        }

        $expected = collect($helper->combinations($variations))->map(fn (array $combination) => implode('-', $combination))->sort()->values()->all();

        $sent = collect($rows)->map(fn (array $row) => implode('-', $row['options']))->sort()->values()->all();

        if ($expected !== $sent) {
            $validator->errors()->add('variation_rows', trans('admin::seller-workspace.add.variation.incomplete'));
        }

        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $skus = $helper->skus($variations, $rows, (string) $this->input('sku'));

        $taken = $helper->takenSkus($skus, $this->route('id') ? (int) $this->route('id') : null);

        $counts = array_count_values([...$skus, (string) $this->input('sku')]);

        foreach ($skus as $index => $sku) {
            if (! preg_match('/^[\p{L}\p{M}\p{N}]+(?:-[\p{L}\p{M}\p{N}]+)*$/u', $sku)) {
                $validator->errors()->add("variation_rows.{$index}.sku", trans('core::validation.slug'));
            } elseif ($counts[$sku] > 1 || in_array($sku, $taken, true)) {
                $validator->errors()->add("variation_rows.{$index}.sku", trans('admin::seller-workspace.add.variation.same-sku'));
            }
        }
    }

    /**
     * Rules for one family field, from its type; every family field may stay empty.
     */
    protected function rulesForField(array $field): array
    {
        $rules = ['nullable'];

        if ($field['type'] === 'select') {
            $rules[] = Rule::in(array_column($field['options'], 'value'));
        } elseif ($field['type'] === 'boolean') {
            $rules[] = 'boolean';
        } elseif ($field['type'] === 'date') {
            $rules[] = 'date';
        } elseif (in_array($field['code'], config('manpleasure-product-entry.numeric_attributes'), true)) {
            array_push($rules, 'numeric', 'min:0');
        } else {
            array_push($rules, 'string', 'max:65535');
        }

        return $rules;
    }
}
