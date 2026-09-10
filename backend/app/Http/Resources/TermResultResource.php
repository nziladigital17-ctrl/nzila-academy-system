<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TermResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'enrollment_id'         => $this->enrollment_id,
            'subject_id'            => $this->subject_id,
            'term_id'               => $this->term_id,
            'grade_book_id'         => $this->grade_book_id,
            'mac'                   => $this->mac !== null ? round((float)$this->mac, 2) : null,
            'pp'                    => $this->pp !== null ? round((float)$this->pp, 2) : null,
            'pt'                    => $this->pt !== null ? round((float)$this->pt, 2) : null,
            'nf_raw'                => $this->nf !== null ? round((float)$this->nf, 2) : null,
            'nf'                    => $this->nf !== null ? (int) round((float)$this->nf, 0, PHP_ROUND_HALF_UP) : null,
            'presences'             => $this->presences,
            'absences_justified'    => $this->absences_justified,
            'absences_unjustified'  => $this->absences_unjustified,
            'total_absences'        => $this->absences_justified + $this->absences_unjustified,
            'attendance_percentage' => $this->attendance_percentage !== null ? (float)$this->attendance_percentage : null,
            'situation'             => $this->situation?->value,
            'situation_label'       => $this->situation?->displayName(),
            'calculated_at'         => $this->calculated_at?->toIso8601String(),
            'subject'               => $this->whenLoaded('subject', fn() => [
                'id'   => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ]),
            'term'                  => $this->whenLoaded('term', fn() => [
                'id'   => $this->term->id,
                'name' => $this->term->name,
            ]),
        ];
    }
}
