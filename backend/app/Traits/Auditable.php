<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Trait Auditable
 *
 * Automatically logs create, update, and delete actions
 * for the model to the audit_logs table.
 *
 * Sensitive fields (passwords, tokens, secrets) are NEVER logged.
 */
trait Auditable
{
    /**
     * Fields that must NEVER be written to audit logs.
     */
    protected static array $auditExclude = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
    ];

    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->logAudit('created', [], $model->getAttributes());
        });

        static::updated(function ($model) {
            $original = $model->getOriginal();
            $changes = $model->getChanges();

            // Remove timestamps from diff
            unset($changes['updated_at'], $original['updated_at']);

            if (!empty($changes)) {
                $oldValues = array_intersect_key($original, $changes);
                $model->logAudit('updated', $oldValues, $changes);
            }
        });

        static::deleted(function ($model) {
            $model->logAudit('deleted', $model->getOriginal(), []);
        });
    }

    /**
     * Write an audit log entry.
     */
    protected function logAudit(string $action, array $oldValues, array $newValues): void
    {
        try {
            // Strip sensitive fields — these must NEVER appear in logs
            $excludeKeys = static::$auditExclude;
            if (property_exists($this, 'auditExcludeExtra')) {
                $excludeKeys = array_merge($excludeKeys, $this->auditExcludeExtra);
            }

            $oldValues = array_diff_key($oldValues, array_flip($excludeKeys));
            $newValues = array_diff_key($newValues, array_flip($excludeKeys));

            AuditLog::create([
                'school_id' => $this->school_id ?? Auth::user()?->school_id,
                'user_id' => Auth::id(),
                'action' => $action,
                'auditable_type' => get_class($this),
                'auditable_id' => $this->getKey(),
                'old_values' => !empty($oldValues) ? $oldValues : null,
                'new_values' => !empty($newValues) ? $newValues : null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Audit logging should never break the application
            report($e);
        }
    }
}
