<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Register a payment against an invoice with transaction.
     */
    public function registerPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'received_by' => Auth::id(),
            ]);

            // Generate receipt
            Receipt::create([
                'payment_id' => $payment->id,
                'receipt_number' => $this->generateReceiptNumber($invoice->school_id),
                'issued_at' => now(),
            ]);

            // Update invoice status
            $totalPaid = $invoice->payments()->sum('amount');
            if ($totalPaid >= $invoice->total) {
                $invoice->update(['status' => 'paid']);
            } else {
                $invoice->update(['status' => 'partial']);
            }

            return $payment->load('receipt');
        });
    }

    /**
     * Generate a unique receipt number.
     */
    private function generateReceiptNumber(int $schoolId): string
    {
        $year = now()->format('Y');
        $count = Receipt::whereHas('payment.invoice', function ($q) use ($schoolId) {
            $q->withoutGlobalScopes()->where('school_id', $schoolId);
        })->whereYear('created_at', $year)->count() + 1;

        return sprintf('REC-%d-%s-%04d', $schoolId, $year, $count);
    }
}
