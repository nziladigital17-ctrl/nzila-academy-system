<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreFinancialAdjustmentRequest;
use App\Http\Requests\Finance\StoreInvoiceRequest;
use App\Http\Requests\Finance\VoidInvoiceRequest;
use App\Http\Resources\Finance\FinancialAdjustmentResource;
use App\Http\Resources\Finance\InvoiceResource;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        $query = Invoice::with(['student', 'items']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('due_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('due_date', '<=', $request->to_date);
        }
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%' . $request->search . '%');
        }

        return InvoiceResource::collection($query->paginate(15));
    }

    public function store(StoreInvoiceRequest $request)
    {
        $student = Student::findOrFail($request->student_id);
        
        $invoice = $this->invoiceService->createInvoice($student, $request->validated());

        return response()->json([
            'message' => 'Fatura criada com sucesso.',
            'data' => new InvoiceResource($invoice),
        ], 201);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['student', 'items', 'payments.receipt', 'adjustments', 'issuedByUser']);

        return new InvoiceResource($invoice);
    }

    public function issue(Invoice $invoice)
    {
        $this->invoiceService->issueInvoice($invoice);

        return response()->json([
            'message' => 'Fatura emitida com sucesso.',
            'data' => new InvoiceResource($invoice->fresh()),
        ]);
    }

    public function void(VoidInvoiceRequest $request, Invoice $invoice)
    {
        $this->invoiceService->voidInvoice($invoice, $request->reason);

        return response()->json([
            'message' => 'Fatura anulada com sucesso.',
            'data' => new InvoiceResource($invoice->fresh()),
        ]);
        try {
            $this->invoiceService->voidInvoice($invoice, $request->reason);
            return response()->json([
                'message' => 'Fatura anulada com sucesso.',
                'data' => new InvoiceResource($invoice->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function adjustments(StoreFinancialAdjustmentRequest $request, Invoice $invoice)
    {
        $adjustment = $this->invoiceService->applyAdjustment($invoice, $request->validated());

        return response()->json([
            'message' => 'Ajuste aplicado com sucesso.',
            'data' => new FinancialAdjustmentResource($adjustment),
        ]);
try {
            $adjustment = $this->invoiceService->applyAdjustment($invoice, $request->validated());
            return response()->json([
                'message' => 'Ajuste aplicado com sucesso.',
                'data' => new FinancialAdjustmentResource($adjustment),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}



