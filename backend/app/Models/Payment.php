<?php
namespace App\Models;
use App\Traits\Auditable;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory, BelongsToSchool, Auditable;
    protected $fillable = [
        'school_id', 'invoice_id', 'amount', 'payment_method', 'reference', 'status',
        'paid_at', 'received_by', 'confirmed_by', 'confirmed_at', 'voided_by', 'voided_at', 'void_reason'
    ];
    protected $casts = [
        'amount' => 'decimal:2', 'paid_at' => 'datetime', 'confirmed_at' => 'datetime', 'voided_at' => 'datetime'
    ];
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function receipt(): HasOne { return $this->hasOne(Receipt::class); }
    public function allocations(): HasMany { return $this->hasMany(PaymentAllocation::class); }
    public function receivedByUser(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function confirmedByUser(): BelongsTo { return $this->belongsTo(User::class, 'confirmed_by'); }
    public function voidedByUser(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }
}

