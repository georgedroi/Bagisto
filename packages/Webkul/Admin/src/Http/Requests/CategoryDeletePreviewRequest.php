<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryDeletePreviewRequest extends FormRequest
{
    /**
     * Require the native category deletion permission even for inventory review.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.categories.delete');
    }

    /**
     * Review one exact category branch.
     */
    public function rules(): array
    {
        return ['category_id' => ['required', 'integer', 'min:1']];
    }
}
