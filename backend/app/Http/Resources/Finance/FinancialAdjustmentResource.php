<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'invoice_id' => $this->invoice_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
            'applied_by' => $this->applied_by,
            'approved_by' => $this->approved_by,
            'invoice' => $this->whenLoaded('invoice'),
            'applied_by_user' => $this->whenLoaded('appliedByUser'),
            'approved_by_user' => $this->whenLoaded('approvedByUser'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
