<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnualResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'enrollment_id'              => $this->enrollment_id,
            'subject_id'                 => $this->subject_id,
            'academic_year_id'           => $this->academic_year_id,
            'mfa_raw'                    => $this->mfa !== null ? round((float)$this->mfa, 2) : null,
            'mfa'                        => $this->mfa !== null ? (int) round((float)$this->mfa, 0, PHP_ROUND_HALF_UP) : null,
            'total_presences'            => $this->total_presences,
            'total_absences_justified'   => $this->total_absences_justified,
            'total_absences_unjustified' => $this->total_absences_unjustified,
            'situation'                  => $this->situation?->value,
            'situation_label'            => $this->situation?->displayName(),
            'calculated_at'              => $this->calculated_at?->toIso8601String(),
            'subject'                    => $this->whenLoaded('subject', fn() => [
                'id'   => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ]),
        ];
    }
}
