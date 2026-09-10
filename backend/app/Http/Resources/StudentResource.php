<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'student_number' => $this->student_number,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'birth_place' => $this->birth_place,
            'nationality' => $this->nationality,
            'bi_number' => $this->bi_number,
            'address' => $this->address,
            'is_active' => $this->is_active,
            'guardians' => GuardianResource::collection($this->whenLoaded('guardians')),
            'enrollments' => EnrollmentResource::collection($this->whenLoaded('enrollments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
