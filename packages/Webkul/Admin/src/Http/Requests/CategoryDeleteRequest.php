<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;
use Webkul\User\Repositories\AdministratorApprovalRepository;

class CategoryDeleteRequest extends CategoryDeletePreviewRequest
{
    /**
     * The full-access administrator verified for this request only.
     */
    protected ?int $approvingAdminId = null;

    /**
     * Product deletion requires its own permission in addition to category deletion.
     */
    public function authorize(): bool
    {
        return parent::authorize()
            && (! $this->boolean('delete_products') || bouncer()->hasPermission('catalog.products.delete'));
    }

    /**
     * Bind confirmation to a fresh session review and explicit product acknowledgment.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'preview_token' => ['required', 'string', 'regex:/\A[a-zA-Z0-9]{40}\z/'],
            'revision' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
            'delete_products' => ['required', 'boolean'],
            'approver_email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:1024'],
            'acknowledge_products' => $this->boolean('delete_products') ? ['required', 'accepted'] : ['nullable', 'boolean'],
        ]);
    }

    /**
     * Require the password of an active full-access administrator without signing them in.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $admin = app(AdministratorApprovalRepository::class)->findActiveFullAccess($this->input('approver_email'));

            if (! $admin || ! Hash::check($this->input('password'), $admin->getAuthPassword())) {
                $validator->errors()->add('password', trans('admin::category-tree.error-delete-password'));

                return;
            }

            $this->approvingAdminId = (int) $admin->id;
        });
    }

    /**
     * Identify the approving administrator after successful request validation.
     */
    public function approvedBy(): int
    {
        return $this->approvingAdminId ?? throw new \LogicException('Administrator approval was not validated.');
    }
}
