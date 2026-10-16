<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryMoveRequest extends FormRequest
{
    /**
     * Require the same permission as the native category edit form.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.categories.edit');
    }

    /**
     * Accept only a bounded move and its exact starting hierarchy revision.
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'min:1'],
            'parent_id' => ['required', 'integer', 'min:1'],
            'before_id' => ['nullable', 'integer', 'min:1'],
            'revision' => ['required', 'string', 'regex:/\\A[a-f0-9]{64}\\z/'],
        ];
    }
}
