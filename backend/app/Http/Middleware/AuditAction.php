<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware AuditAction
 *
 * Logs authentication-related actions (login, logout, password change)
 * that are not covered by the Auditable trait on models.
 *
 * Usage: ->middleware('audit:login')
 */
class AuditAction
{
    public function handle(Request $request, Closure $next, string $action = 'api_request'): Response
    {
        $response = $next($request);

        // Only log successful actions
        if ($response->isSuccessful() || $response->isRedirection()) {
            try {
                AuditLog::create([
                    'school_id' => $request->user()?->school_id,
                    'user_id' => $request->user()?->id,
                    'action' => $action,
                    'auditable_type' => 'App\\Models\\User',
                    'auditable_id' => $request->user()?->id,
                    'old_values' => null,
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $response;
    }
}
