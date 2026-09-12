<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'receipt_number' => $this->receipt_number,
            'issued_at' => $this->issued_at ? $this->issued_at->toIso8601String() : null,
            'status' => $this->status,
            'voided_at' => $this->voided_at ? $this->voided_at->toIso8601String() : null,
            'voided_by' => $this->voided_by,
            'void_reason' => $this->void_reason,
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
