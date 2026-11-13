<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Inventory\Repositories\InventorySourceRepository;

class ProductStockRequest extends FormRequest
{
    /**
     * Most batches one warehouse can hold for a product.
     */
    public const MAX_BATCHES = 5;

    /**
     * Determine if the admin may edit stock; the route's permission has already decided.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for a whole quantity per active warehouse and its dated batches.
     */
    public function rules(): array
    {
        $sources = app(InventorySourceRepository::class)->findWhere(['status' => 1])->pluck('id')->implode(',');

        return [
            'inventories' => ['required', 'array', 'min:1', 'array:'.$sources],
            'inventories.*' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'batches_sync' => ['nullable', 'boolean'],
            'batches' => ['nullable', 'array', 'array:'.$sources],
            'batches.*' => ['array', 'max:'.self::MAX_BATCHES],
            'batches.*.*.quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'batches.*.*.ship_from' => ['required', 'date_format:Y-m-d'],
            'batches.*.*.ship_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:batches.*.*.ship_from'],
        ];
    }

    /**
     * Get the messages for batch fields, whose generated names mean nothing to the admin.
     */
    public function messages(): array
    {
        return [
            'batches.*.*.quantity.*' => trans('admin::seller-workspace.editor.batch-quantity'),
            'batches.*.*.ship_from.*' => trans('admin::seller-workspace.editor.batch-dates'),
            'batches.*.*.ship_to.*' => trans('admin::seller-workspace.editor.batch-dates'),
        ];
    }
}
