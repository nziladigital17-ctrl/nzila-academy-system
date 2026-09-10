<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'school_id'             => $this->school_id,
            'academic_year_id'      => $this->academic_year_id,
            'term_id'               => $this->term_id,
            'class_id'              => $this->class_id,
            'subject_id'            => $this->subject_id,
            'teacher_assignment_id' => $this->teacher_assignment_id,
            'type'                  => $this->type?->value,
            'type_label'            => $this->type?->displayName(),
            'label'                 => $this->label,
            'date'                  => $this->date?->format('Y-m-d'),
            'is_active'             => $this->is_active,
            'subject'               => $this->whenLoaded('subject', fn() => [
                'id'   => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ]),
            'term'                  => $this->whenLoaded('term', fn() => [
                'id'   => $this->term->id,
                'name' => $this->term->name,
            ]),
            'grades_count'          => $this->whenLoaded('activeGrades', fn() => $this->activeGrades->count()),
            'created_at'            => $this->created_at?->toIso8601String(),
        ];
    }
}
