<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'invoice_id' => $this->invoice_id,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'paid_at' => $this->paid_at ? $this->paid_at->toIso8601String() : null,
            'status' => $this->status,
            'confirmed_at' => $this->confirmed_at ? $this->confirmed_at->toIso8601String() : null,
            'confirmed_by' => $this->confirmed_by,
            'voided_at' => $this->voided_at ? $this->voided_at->toIso8601String() : null,
            'voided_by' => $this->voided_by,
            'void_reason' => $this->void_reason,
            'received_by' => $this->received_by,
            'receipt' => new ReceiptResource($this->whenLoaded('receipt')),
            'allocations' => PaymentAllocationResource::collection($this->whenLoaded('allocations')),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
