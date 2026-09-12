<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'expenses_count' => $this->whenCounted('expenses'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
