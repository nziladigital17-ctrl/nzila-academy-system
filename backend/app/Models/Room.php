<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes, BelongsToSchool, Auditable;

    protected $fillable = [
        'school_id',
        'name',
        'capacity',
        'building',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function classes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }
}
