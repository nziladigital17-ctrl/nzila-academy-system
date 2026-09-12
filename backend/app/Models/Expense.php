<?php
namespace App\Models;
use App\Traits\Auditable;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes, BelongsToSchool, Auditable;
    protected $fillable = [
        'school_id', 'expense_category_id', 'category', 'description', 'amount', 'date',
        'status', 'approved_by', 'confirmed_by', 'confirmed_at', 'voided_by', 'voided_at', 'void_reason'
    ];
    protected $casts = [
        'amount' => 'decimal:2', 'date' => 'date', 'confirmed_at' => 'datetime', 'voided_at' => 'datetime'
    ];
    public function expenseCategory(): BelongsTo { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function approvedByUser(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function confirmedByUser(): BelongsTo { return $this->belongsTo(User::class, 'confirmed_by'); }
    public function voidedByUser(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }
}
