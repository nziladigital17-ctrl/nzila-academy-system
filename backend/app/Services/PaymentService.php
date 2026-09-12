<?php
namespace App\Services;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function registerPayment(array $data, int $schoolId): Payment
    {
        return DB::transaction(function () use ($data, $schoolId) {
            $payment = Payment::create([
                'school_id' => $schoolId,
                'invoice_id' => $data['invoice_id'] ?? null,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'status' => 'draft',
                'paid_at' => now(),
                'received_by' => Auth::id(),
            ]);
            
            if (isset($data['allocations']) && is_array($data['allocations'])) {
                foreach ($data['allocations'] as $alloc) {
                    $payment->allocations()->create([
                        'invoice_id' => $alloc['invoice_id'],
                        'amount' => $alloc['amount']
                    ]);
                }
            } elseif (isset($data['invoice_id'])) {
                $payment->allocations()->create([
                    'invoice_id' => $data['invoice_id'],
                    'amount' => $data['amount']
                ]);
            }
            return $payment->fresh(['allocations']);
        });
    }

    public function confirmPayment(Payment $payment): Payment
    {
        if ($payment->status !== 'draft') throw new \InvalidArgumentException('Apenas pagamentos em rascunho podem ser confirmados.');
        return DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'confirmed', 'confirmed_at' => now(), 'confirmed_by' => Auth::id(),
            ]);
            if (!$payment->receipt) {
                Receipt::create([
                    'payment_id' => $payment->id,
                    'receipt_number' => $this->generateReceiptNumber($payment->school_id),
                    'issued_at' => now(),
                ]);
            }
            $invoiceIds = $payment->allocations()->pluck('invoice_id')->unique();
            if ($invoiceIds->isEmpty() && $payment->invoice_id) $invoiceIds = collect([$payment->invoice_id]);
            foreach ($invoiceIds as $invoiceId) {
                $invoice = Invoice::withoutGlobalScopes()->find($invoiceId);
                if ($invoice) $this->invoiceService->recalculateStatus($invoice);
            }
            return $payment->fresh(['receipt', 'allocations']);
        });
    }

    public function voidPayment(Payment $payment, string $reason): Payment
    {
        if ($payment->status !== 'confirmed') throw new \InvalidArgumentException('Apenas pagamentos confirmados podem ser anulados.');
        return DB::transaction(function () use ($payment, $reason) {
            $payment->update([
                'status' => 'voided', 'voided_at' => now(), 'voided_by' => Auth::id(), 'void_reason' => $reason,
            ]);
            if ($payment->receipt) {
                $payment->receipt->update([
                    'status' => 'voided', 'voided_at' => now(), 'voided_by' => Auth::id(), 'void_reason' => $reason,
                ]);
            }
            $invoiceIds = $payment->allocations()->pluck('invoice_id')->unique();
            if ($invoiceIds->isEmpty() && $payment->invoice_id) $invoiceIds = collect([$payment->invoice_id]);
            foreach ($invoiceIds as $invoiceId) {
                $invoice = Invoice::withoutGlobalScopes()->find($invoiceId);
                if ($invoice) $this->invoiceService->recalculateStatus($invoice);
            }
            return $payment->fresh(['receipt', 'allocations']);
        });
    }

    public function refundPayment(Payment $payment, string $reason): Payment
    {
        if ($payment->status !== 'confirmed') throw new \InvalidArgumentException('Apenas pagamentos confirmados podem ser estornados.');
        return DB::transaction(function () use ($payment, $reason) {
            $payment->update([
                'status' => 'refunded', 'voided_at' => now(), 'voided_by' => Auth::id(), 'void_reason' => $reason,
            ]);
            if ($payment->receipt) {
                $payment->receipt->update([
                    'status' => 'voided', 'voided_at' => now(), 'voided_by' => Auth::id(), 'void_reason' => 'Estorno: ' . $reason,
                ]);
            }
            $invoiceIds = $payment->allocations()->pluck('invoice_id')->unique();
            if ($invoiceIds->isEmpty() && $payment->invoice_id) $invoiceIds = collect([$payment->invoice_id]);
            foreach ($invoiceIds as $invoiceId) {
                $invoice = Invoice::withoutGlobalScopes()->find($invoiceId);
                if ($invoice) $this->invoiceService->recalculateStatus($invoice);
            }
            return $payment->fresh(['receipt', 'allocations']);
        });
    }

    public function generateReceiptNumber(int $schoolId): string
    {
        $year = now()->format('Y');
        $count = Receipt::whereHas('payment', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })->whereYear('created_at', $year)->count() + 1;
        return sprintf('REC-%d-%s-%04d', $schoolId, $year, $count);
    }
}
