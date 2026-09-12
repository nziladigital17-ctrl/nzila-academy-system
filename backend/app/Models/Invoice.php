<?php
namespace App\Models;
use App\Traits\Auditable;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToSchool, Auditable;
    protected $fillable = [
        'school_id', 'student_id', 'invoice_number', 'due_date', 'subtotal', 'discount', 'total', 'status', 'issued_by',
        'voided_by', 'voided_at', 'void_reason'
    ];
    protected $casts = [
        'due_date' => 'date', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2', 'voided_at' => 'datetime'
    ];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function adjustments(): HasMany { return $this->hasMany(FinancialAdjustment::class); }
    public function issuedByUser(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
    public function voidedByUser(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }
    public function amountPaid(): float { return (float) $this->payments()->whereNull('voided_at')->sum('amount'); }
    public function balance(): float { return (float) $this->total - $this->amountPaid(); }
    public function isOverdue(): bool { return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast(); }
}



