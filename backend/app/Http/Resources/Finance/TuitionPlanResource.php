<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TuitionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'academic_year_id' => $this->academic_year_id,
            'code' => $this->code,
            'name' => $this->name,
            'grade_level' => $this->grade_level,
            'class_id' => $this->class_id,
            'student_id' => $this->student_id,
            'amount' => $this->amount,
            'installments' => $this->installments,
            'periodicity' => $this->periodicity,
            'due_day' => $this->due_day,
            'is_active' => $this->is_active,
            'academic_year' => $this->whenLoaded('academic_year'),
            'class' => $this->whenLoaded('school_class'),
            'student' => $this->whenLoaded('student'),
            'assignments_count' => $this->whenCounted('assignments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
