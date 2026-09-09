<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Create an invoice with items in a transaction.
     */
    public function createInvoice(Student $student, array $items, ?string $dueDate = null): Invoice
    {
        return DB::transaction(function () use ($student, $items, $dueDate) {
            $subtotal = collect($items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $discount = 0;
            $total = $subtotal - $discount;

            $invoice = Invoice::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'invoice_number' => $this->generateInvoiceNumber($student->school_id),
                'due_date' => $dueDate ?? now()->addDays(30),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => 'pending',
                'issued_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
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

    /**
     * Generate a unique invoice number.
     */
    private function generateInvoiceNumber(int $schoolId): string
    {
        $year = now()->format('Y');
        $count = Invoice::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('FAT-%d-%s-%04d', $schoolId, $year, $count);
    }
}
