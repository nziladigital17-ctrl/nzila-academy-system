<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware CheckPermission
 *
 * Verifies that the authenticated user has the required permission
 * through one of their assigned roles.
 *
 * Usage: ->middleware('permission:students.view')
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Autenticação necessária.');
        }

        // Super admins have all permissions
        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $next($request);
        }

        // Check if user has the required permission through any of their roles
        if (!$user->hasPermission($permission)) {
            abort(403, 'Não tem permissão para realizar esta acção.');
        }

        return $next($request);
    }
}
