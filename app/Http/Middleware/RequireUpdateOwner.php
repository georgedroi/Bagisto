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
        $admin = auth('admin')->user();

        abort_unless(
            $admin && $admin->status
            && $admin->role?->permission_type === 'all'
            && in_array((int) $admin->id, config('manpleasure-updates.owner_ids', []), true),
            403
        );

        return $next($request);
    }
}
