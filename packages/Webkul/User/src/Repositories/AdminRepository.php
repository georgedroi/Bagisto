<?php

namespace Webkul\User\Repositories;

use Webkul\Core\Eloquent\Repository;

class AdminRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\User\Contracts\Admin';
    }

    /**
     * Consume a single backup code while locking the administrator's current codes.
     */
    public function consumeTwoFactorBackupCode(int $id, string $code): bool
    {
        return $this->model->getConnection()->transaction(function () use ($id, $code) {
            $admin = $this->model->newQuery()->lockForUpdate()->findOrFail($id);
            $codes = two_factor_authentication()->verifyBackupCode($admin->two_factor_backup_codes ?? [], $code);

            if ($codes === null) {
                return false;
            }

            $admin->update(['two_factor_backup_codes' => $codes]);

            return true;
        });
    }

    /**
     * Count admins with all access.
     */
    public function countAdminsWithAllAccess(): int
    {
        return $this->getModel()
            ->leftJoin('roles', 'admins.role_id', '=', 'roles.id')
            ->where('roles.permission_type', 'all')
            ->get()
            ->count();
    }

    /**
     * Count admins with all access and active status.
     */
    public function countAdminsWithAllAccessAndActiveStatus(): int
    {
        return $this->getModel()
            ->leftJoin('roles', 'admins.role_id', '=', 'roles.id')
            ->where('admins.status', 1)
            ->where('roles.permission_type', 'all')
            ->get()
            ->count();
    }
}
