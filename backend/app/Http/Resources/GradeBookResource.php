<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeBookResource extends JsonResource
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
            'status'                => $this->status?->value,
            'status_label'          => $this->status?->displayName(),
            'grades_editable'       => $this->gradesEditable(),
            'submitted_at'          => $this->submitted_at?->toIso8601String(),
            'published_at'          => $this->published_at?->toIso8601String(),
            'locked_at'             => $this->locked_at?->toIso8601String(),
            'unlock_reason'         => $this->unlock_reason,
            'subject'               => $this->whenLoaded('subject', fn() => [
                'id'   => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ]),
            'term'                  => $this->whenLoaded('term', fn() => [
                'id'   => $this->term->id,
                'name' => $this->term->name,
            ]),
            'class'                 => $this->whenLoaded('schoolClass', fn() => [
                'id'   => $this->schoolClass->id,
                'name' => $this->schoolClass->name,
            ]),
            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}
