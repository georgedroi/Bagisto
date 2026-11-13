<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireUpdateOwner
{
    /**
     * Restrict deployment controls to configured active owners with full admin access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(self::allows(auth('admin')->user()), 403);

        return $next($request);
    }

    /**
     * Tell whether this admin is an active configured owner with full admin access.
     */
    public static function allows($admin): bool
    {
        return $admin && $admin->status
            && $admin->role?->permission_type === 'all'
            && in_array((int) $admin->id, config('manpleasure-updates.owner_ids', []), true);
    }
}
