<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes, BelongsToSchool, Auditable;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'weekly_hours',
        'is_active',
    ];

    protected $casts = [
        'weekly_hours' => 'integer',
        'is_active' => 'boolean',
    ];

    public function classes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }
}
