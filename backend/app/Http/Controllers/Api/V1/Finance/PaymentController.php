<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StorePaymentRequest;
use App\Http\Requests\Finance\VoidPaymentRequest;
use App\Http\Resources\Finance\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $query = Payment::with(['receipt', 'allocations.invoice']);

        if ($request->filled('invoice_id')) {
            $query->whereHas('allocations', function ($q) use ($request) {
                $q->where('invoice_id', $request->invoice_id);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        return PaymentResource::collection($query->paginate(15));
    }

    public function store(StorePaymentRequest $request)
    {
        $data = $request->validated();
        if (empty($data['school_id'])) {
            $data['school_id'] = auth()->user()->school_id;
        }

        $payment = $this->paymentService->registerPayment($data, $data['school_id']);
        $payment = $this->paymentService->confirmPayment($payment);

        return response()->json([
            'message' => 'Pagamento registrado com sucesso.',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    public function show(Payment $payment)
    {
        $payment->load(['receipt', 'allocations.invoice', 'invoice.student']);

        return new PaymentResource($payment);
    }

    public function confirm(Payment $payment)
    {
        $this->paymentService->confirmPayment($payment);

        return response()->json([
            'message' => 'Pagamento confirmado com sucesso.',
            'data' => new PaymentResource($payment->fresh()),
        ]);
    }

    public function void(VoidPaymentRequest $request, Payment $payment)
    {
        $this->paymentService->voidPayment($payment, $request->reason);

        return response()->json([
            'message' => 'Pagamento anulado com sucesso.',
            'data' => new PaymentResource($payment->fresh()),
        ]);
    }

    public function refund(VoidPaymentRequest $request, Payment $payment)
    {
        $this->paymentService->refundPayment($payment, $request->reason);

        return response()->json([
            'message' => 'Pagamento reembolsado com sucesso.',
            'data' => new PaymentResource($payment->fresh()),
        ]);
        try {
            $this->paymentService->refundPayment($payment, $request->reason);
            return response()->json([
                'message' => 'Pagamento reembolsado com sucesso.',
                'data' => new PaymentResource($payment->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}




