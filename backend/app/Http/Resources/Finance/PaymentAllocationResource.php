<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'invoice_id' => $this->invoice_id,
            'amount' => $this->amount,
            'invoice' => $this->whenLoaded('invoice', function () {
                return [
                    'invoice_number' => $this->invoice->invoice_number,
                    'student_id' => $this->invoice->student_id,
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}
