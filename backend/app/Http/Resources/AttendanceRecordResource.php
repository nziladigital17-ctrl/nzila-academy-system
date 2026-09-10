<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'attendance_session_id' => $this->attendance_session_id,
            'enrollment_id'         => $this->enrollment_id,
            'status'                => $this->status?->value,
            'status_label'          => $this->status?->displayName(),
            'justification'         => $this->justification,
            'student'               => $this->whenLoaded('enrollment', fn() => [
                'id'   => $this->enrollment->student?->id,
                'name' => $this->enrollment->student?->full_name,
                'number' => $this->enrollment->student?->student_number,
            ]),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}
