<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'student_id' => $this->student_id,
            'class_id' => $this->class_id,
            'enrolled_at' => $this->enrolled_at?->format('Y-m-d'),
            'status' => $this->status,
            'student' => new StudentResource($this->whenLoaded('student')),
            'class' => new ClassResource($this->whenLoaded('schoolClass')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
