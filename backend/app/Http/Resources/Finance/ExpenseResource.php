<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'expense_category_id' => $this->expense_category_id,
            'category' => $this->category,
            'description' => $this->description,
            'amount' => $this->amount,
            'date' => $this->date ? (\Carbon\Carbon::parse($this->date)->format('Y-m-d')) : null,
            'status' => $this->status,
            'confirmed_at' => $this->confirmed_at ? (\Carbon\Carbon::parse($this->confirmed_at)->toIso8601String()) : null,
            'confirmed_by' => $this->confirmed_by,
            'voided_at' => $this->voided_at ? (\Carbon\Carbon::parse($this->voided_at)->toIso8601String()) : null,
            'voided_by' => $this->voided_by,
            'void_reason' => $this->void_reason,
            'approved_by' => $this->approved_by,
            'category_relation' => new ExpenseCategoryResource($this->whenLoaded('category')),
            'category_relation' => new ExpenseCategoryResource($this->whenLoaded('expenseCategory')),
            'approved_by_user' => $this->whenLoaded('approvedByUser'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
