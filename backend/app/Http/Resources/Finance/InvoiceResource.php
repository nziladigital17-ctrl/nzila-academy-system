<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\StudentResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'student_id' => $this->student_id,
            'invoice_number' => $this->invoice_number,
            'due_date' => $this->due_date ? $this->due_date->format('Y-m-d') : null,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'status' => $this->status,
            'notes' => $this->notes,
            'amount_paid' => $this->amountPaid(),
            'balance' => $this->balance(),
            'is_overdue' => $this->isOverdue(),
            'issued_by' => $this->issued_by,
            'voided_at' => $this->voided_at ? $this->voided_at->toIso8601String() : null,
            'voided_by' => $this->voided_by,
            'void_reason' => $this->void_reason,
            'student' => new StudentResource($this->whenLoaded('student')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'adjustments' => FinancialAdjustmentResource::collection($this->whenLoaded('adjustments')),
            'issued_by_user' => $this->whenLoaded('issued_by_user'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
