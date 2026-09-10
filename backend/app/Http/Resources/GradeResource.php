<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'assessment_id'   => $this->assessment_id,
            'enrollment_id'   => $this->enrollment_id,
            'grade_book_id'   => $this->grade_book_id,
            'score'           => (float) $this->score,
            'remarks'         => $this->remarks,
            'is_annulled'     => $this->is_annulled,
            'annulled_reason' => $this->when($this->is_annulled, $this->annulled_reason),
            'annulled_at'     => $this->when($this->is_annulled, $this->annulled_at?->toIso8601String()),
            'assessment'      => $this->whenLoaded('assessment', fn() => [
                'id'    => $this->assessment->id,
                'type'  => $this->assessment->type?->value,
                'label' => $this->assessment->label,
                'date'  => $this->assessment->date?->format('Y-m-d'),
            ]),
            'graded_by'       => $this->whenLoaded('gradedByUser', fn() => [
                'id'   => $this->gradedByUser->id,
                'name' => $this->gradedByUser->name,
            ]),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
