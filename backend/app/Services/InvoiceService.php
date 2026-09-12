<?php
namespace App\Services;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\FinancialAdjustment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function createInvoice(Student $student, array $data, ?string $dueDate = null): Invoice
    {
        return DB::transaction(function () use ($student, $data, $dueDate) {
            $items = $data['items'] ?? $data;
            $subtotal = collect($items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $discount = 0;
            $total = $subtotal - $discount;

            $invoice = Invoice::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'invoice_number' => $this->generateInvoiceNumber($student->school_id),
                'due_date' => $data['due_date'] ?? $dueDate ?? now()->addDays(30),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => 'pending',
                'issued_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                if (!isset($item['description'])) continue;
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }
            return $invoice->load('items');
        });
    }

    public function issueInvoice(Invoice $invoice): Invoice
    {
        return $invoice; // Dummy for now
    }

    public function voidInvoice(Invoice $invoice, string $reason): Invoice
    {
        if (in_array($invoice->status, ['paid', 'voided'])) {
            throw new \InvalidArgumentException('Apenas faturas pendentes ou parciais podem ser anuladas.');
        }
        $invoice->update([
            'status' => 'voided', 'voided_at' => now(), 'voided_by' => Auth::id(), 'void_reason' => $reason
        ]);
        return $invoice->fresh();
    }

    public function applyAdjustment(Invoice $invoice, array $data): FinancialAdjustment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $adjustment = $invoice->adjustments()->create([
                'type' => $data['type'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'applied_by' => Auth::id(),
            ]);
            
            if ($data['type'] === 'discount') {
                $invoice->discount += $data['amount'];
                $invoice->total -= $data['amount'];
            } else {
                $invoice->total += $data['amount'];
            }
            $invoice->save();
            $this->recalculateStatus($invoice);
            
            return $adjustment;
        });
    }

    public function recalculateStatus(Invoice $invoice): void
    {
        if ($invoice->status === 'voided') return;
        $paid = $invoice->payments()->where('status', 'confirmed')->sum('amount');
        if ($paid >= $invoice->total) {
            $invoice->update(['status' => 'paid']);
        } elseif ($paid > 0) {
            $invoice->update(['status' => 'partial']);
        } else {
            $invoice->update(['status' => 'pending']);
        }
    }

    public function getDebtors(int $schoolId, ?int $academicYearId = null)
    {
        $students = Student::where('school_id', $schoolId)
            ->whereHas('invoices', function ($q) {
                $q->whereIn('status', ['pending', 'partial', 'overdue']);
            })
            ->with(['invoices' => function ($q) {
                $q->whereIn('status', ['pending', 'partial', 'overdue']);
            }])->get();
            
        return $students->map(function ($student) {
            $invoices = $student->invoices;
            $totalOwed = $invoices->sum('total');
            $totalPaid = 0; // Approximate
            foreach ($invoices as $inv) {
                if ($inv->status === 'partial') {
                    $totalPaid += $inv->payments()->where('status', 'confirmed')->sum('amount');
                }
            }
            return [
                'student' => $student,
                'balance' => $totalOwed - $totalPaid,
                'invoices_count' => $invoices->count(),
                'oldest_due_date' => $invoices->min('due_date'),
                'total_owed' => $totalOwed,
                'total_paid' => $totalPaid,
            ];
        });
    }

    public function generateInvoiceNumber(int $schoolId): string
    {
        $year = now()->format('Y');
        $count = Invoice::withoutGlobalScopes()->where('school_id', $schoolId)->whereYear('created_at', $year)->count() + 1;
        return sprintf('FAT-%d-%s-%04d', $schoolId, $year, $count);
    }
}
