<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function createExpense(array $data, int $schoolId): Expense
    {
        return Expense::create([
            'school_id' => $schoolId,
            'expense_category_id' => $data['expense_category_id'] ?? null,
            'category' => $data['category'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'date' => $data['date'],
            'status' => 'draft',
        ]);
    }

    public function confirmExpense(Expense $expense): Expense
    {
        if ($expense->status !== 'draft') {
            throw new \InvalidArgumentException('Apenas despesas em rascunho podem ser confirmadas.');
        }

        $expense->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => Auth::id(),
            'approved_by' => Auth::id(),
        ]);

        return $expense->fresh();
    }

    public function voidExpense(Expense $expense, string $reason): Expense
    {
        if ($expense->status === 'voided') {
            throw new \InvalidArgumentException('Esta despesa já está anulada.');
        }

        $expense->update([
            'status' => 'voided',
            'voided_at' => now(),
            'voided_by' => Auth::id(),
            'void_reason' => $reason,
        ]);

        return $expense->fresh();
    }
}
