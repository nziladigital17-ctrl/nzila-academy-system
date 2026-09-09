<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware EnsureBelongsToSchool
 *
 * Validates that the route-bound model belongs to the authenticated user's school.
 * Super admins (no school_id) bypass this check.
 */
class EnsureBelongsToSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admins can access any school's data
        if (!$user || !$user->school_id) {
            return $next($request);
        }

        // Check all route parameters for models with school_id
        foreach ($request->route()->parameters() as $parameter) {
            if (
                is_object($parameter)
                && method_exists($parameter, 'getAttribute')
                && $parameter->getAttribute('school_id')
                && $parameter->getAttribute('school_id') !== $user->school_id
            ) {
                abort(403, 'Não tem permissão para aceder a dados de outra escola.');
            }
        }

        return $next($request);
    }
}
