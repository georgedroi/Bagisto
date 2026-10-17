<?php

namespace Webkul\User\Repositories;

use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
use Webkul\User\Contracts\Admin;

class AdministratorApprovalRepository extends Repository
{
    /**
     * Resolve the native administrator model.
     */
    public function model(): string
    {
        return Admin::class;
    }

    /**
     * Read current account and role state without a repository cache.
     */
    public function findActiveFullAccess(string $email): ?Admin
    {
        return $this->model->newQuery()->with('role')
            ->whereRaw('LOWER(email) = ?', [Str::lower(trim($email))])
            ->where('status', 1)
            ->whereHas('role', fn ($query) => $query->where('permission_type', 'all'))
            ->first();
    }
}
