<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\StudentResource;

class StudentFeeAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'student_id' => $this->student_id,
            'tuition_plan_id' => $this->tuition_plan_id,
            'academic_year_id' => $this->academic_year_id,
            'discount_percent' => $this->discount_percent,
            'discount_fixed' => $this->discount_fixed,
            'scholarship_type' => $this->scholarship_type,
            'effective_amount' => $this->effectiveAmount(),
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'student' => new StudentResource($this->whenLoaded('student')),
            'tuition_plan' => new TuitionPlanResource($this->whenLoaded('tuition_plan')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
