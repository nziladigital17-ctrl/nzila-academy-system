<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'school_id'         => $this->school_id,
            'academic_year_id'  => $this->academic_year_id,
            'min_grade'         => (float) $this->min_grade,
            'max_grade'         => (float) $this->max_grade,
            'passing_grade'     => (float) $this->passing_grade,
            'num_terms'         => $this->num_terms,
            'active_components' => $this->active_components ?? ['AC', 'PP', 'PT'],
            'nf_formula'        => $this->nf_formula,
            'mfa_formula'       => $this->mfa_formula,
            'rounding_mode'     => $this->rounding_mode,
            'pp_active'         => $this->pp_active,
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }
}
