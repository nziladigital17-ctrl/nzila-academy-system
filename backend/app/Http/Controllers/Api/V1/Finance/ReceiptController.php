<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\VoidPaymentRequest;
use App\Http\Resources\Finance\ReceiptResource;
use App\Models\Receipt;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = Receipt::with(['payment.invoice.student']);

        $user = auth()->user();
        if ($user && $user->school_id) {
            $query->whereHas('payment', function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('issue_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('issue_date', '<=', $request->to_date);
        }

        return ReceiptResource::collection($query->paginate(15));
    }

    public function show(Receipt $receipt)
    {
        $receipt->load(['payment.invoice.student']);

        return new ReceiptResource($receipt);
    }

    public function void(VoidPaymentRequest $request, Receipt $receipt)
    {
        if ($receipt->status !== 'active') {
            return response()->json([
                'message' => 'Apenas recibos ativos podem ser anulados.'
            ], 422);
        }

        $receipt->update([
            'status' => 'voided',
            'reason' => $request->reason,
            'voided_by' => auth()->id(),
            'voided_at' => now(),
        ]);

        return response()->json([
            'message' => 'Recibo anulado com sucesso.',
            'data' => new ReceiptResource($receipt),
        ]);
    }
}
