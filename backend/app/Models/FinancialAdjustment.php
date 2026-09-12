<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialAdjustment extends Model
{
    use HasFactory, BelongsToSchool, Auditable;

    public const TYPES = ['discount', 'scholarship', 'exemption', 'penalty', 'correction', 'refund'];

    protected $fillable = [
        'school_id',
        'invoice_id',
        'type',
        'amount',
        'description',
        'applied_by',
        'approved_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function appliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
