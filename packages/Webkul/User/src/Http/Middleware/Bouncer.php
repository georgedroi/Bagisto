<?php

namespace Webkul\User\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Bouncer
{
    /**
     * Routes every signed-in admin may reach whatever their role grants. They either
     * act on the admin's own record, or back shared UI - notifications, the datagrid
     * chrome, the editor's uploader - that no single permission owns. Anything not
     * listed here and not mapped in `acl.php` is refused, so a route added without an
     * ACL entry fails closed instead of being silently open to every role.
     */
    const UNRESTRICTED_ROUTES = [
        'admin.account.edit',
        'admin.account.update',
        'admin.two_factor.enable',
        'admin.two_factor.disable',
        'admin.settings.users.destroy',
        'admin.help.index',
        'admin.notification.index',
        'admin.notification.get_notification',
        'admin.notification.read_all',
        'admin.notification.viewed_notification',
        'admin.datagrid.look_up',
        'admin.datagrid.saved_filters.index',
        'admin.datagrid.saved_filters.store',
        'admin.datagrid.saved_filters.update',
        'admin.datagrid.saved_filters.destroy',
        'admin.magic_ai.content',
        'admin.magic_ai.image',
        'admin.tinymce.upload',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, \Closure $next, $guard = 'admin')
    {
        if (! auth()->guard($guard)->check()) {
            return redirect()->route('admin.session.create');
        }

        if (! (bool) auth()->guard($guard)->user()->status) {
            auth()->guard($guard)->logout();
            session()->invalidate();

            return redirect()->route('admin.session.create');
        }

        if ($this->isPermissionsEmpty()) {
            auth()->guard($guard)->logout();
            session()->invalidate();

            session()->flash('error', trans('admin::app.errors.403.title'));

            return redirect()->route('admin.session.create');
        }

        if ($request->routeIs(
            'admin.two_factor.setup',
            'admin.two_factor.verify.form',
            'admin.two_factor.verify.store',
            'admin.session.destroy'
        )) {
            return $next($request);
        }

        if ($this->isTwoFactorRequired($guard)) {
            return $this->handleTwoFactorRedirect($guard);
        }

        $this->checkIfAuthorized();

        return $next($request);
    }

    /**
     * Check for user, if they have empty permissions or not except admin.
     *
     * @return bool
     */
    public function isPermissionsEmpty()
    {
        if (! $role = auth()->guard('admin')->user()->role) {
            abort(401, trans('admin::app.errors.401.title'));
        }

        if ($role->permission_type === 'all') {
            return false;
        }

        if (
            $role->permission_type !== 'all'
            && empty($role->permissions)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check authorization.
     *
     * @return null
     */
    public function checkIfAuthorized()
    {
        $routeName = Route::currentRouteName();

        if (in_array($routeName, self::UNRESTRICTED_ROUTES)) {
            return;
        }

        $roles = acl()->getRoles();

        if (! isset($roles[$routeName])) {
            abort(401, trans('admin::app.errors.401.title'));
        }

        bouncer()->allow($roles[$routeName]);
    }

    /**
     * Check if two-factor authentication is required.
     */
    public function isTwoFactorRequired(string $guard): bool
    {
        $admin = auth()->guard($guard)->user();

        return $admin->two_factor_enabled && ! $this->hasPassedTwoFactor();
    }

    /**
     * Determine if two-factor authentication has been passed for this session.
     */
    protected function hasPassedTwoFactor(): bool
    {
        return (bool) session('two_factor_passed', false);
    }

    /**
     * Redirect to the correct two-factor flow.
     */
    public function handleTwoFactorRedirect(string $guard)
    {
        $admin = auth()->guard($guard)->user();

        if ($admin->two_factor_secret) {
            return redirect()->route('admin.two_factor.verify.form');
        }

        return redirect()->route('admin.two_factor.setup');
    }
}
