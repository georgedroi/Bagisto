<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\Decimal;

class ProductPricingRequest extends FormRequest
{
    /**
     * Determine if the admin may edit pricing; the route's permission has already decided.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for a price, an optional special price with its dates, and an optional cost.
     */
    public function rules(): array
    {
        return [
            'price' => ['required', new Decimal, 'numeric', 'gt:0'],
            'special_price' => ['nullable', new Decimal, 'numeric', 'gt:0', 'lt:price'],
            'special_price_from' => ['nullable', 'date'],
            'special_price_to' => ['nullable', 'date', 'after_or_equal:special_price_from'],
            'cost' => ['nullable', new Decimal, 'numeric', 'min:0'],
        ];
    }
}
