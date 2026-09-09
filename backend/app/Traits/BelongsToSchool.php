<?php

namespace App\Traits;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Trait BelongsToSchool
 *
 * Apply to any model that must be scoped to a school.
 * Automatically filters queries by the authenticated user's school_id.
 * Super admins bypass the scope.
 */
trait BelongsToSchool
{
    /**
     * Boot the trait — register the global scope.
     */
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $builder) {
            $user = Auth::user();

            if ($user && $user->school_id) {
                $builder->where($builder->getModel()->getTable() . '.school_id', $user->school_id);
            }
        });

        // Automatically set school_id on creating
        static::creating(function ($model) {
            $user = Auth::user();

            if ($user && $user->school_id && empty($model->school_id)) {
                $model->school_id = $user->school_id;
            }
        });
    }

    /**
     * Relationship to the school.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
