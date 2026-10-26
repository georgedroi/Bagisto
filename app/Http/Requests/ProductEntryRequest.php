<?php

namespace App\Http\Requests;

use App\Support\ProductEntryCatalog;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
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
     * Get the validation rules, including the rules for the fields of the category's family.
     */
    public function rules(): array
    {
        $catalog = app(ProductEntryCatalog::class);

        $rules = [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'unique:products,sku', new Slug],
            'url_key' => ['required', new ProductCategoryUniqueSlug('products')],
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
            'new' => ['sometimes', 'boolean'],
            'featured' => ['sometimes', 'boolean'],
            'mp_product_type' => ['required', Rule::in(array_column($catalog->productTypes(), 'value'))],
            'images.files.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

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
        });
    }

    /**
     * Rules for one family field, from its type and whether it is required.
     */
    protected function rulesForField(array $field): array
    {
        $rules = [$field['required'] ? 'required' : 'nullable'];

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
