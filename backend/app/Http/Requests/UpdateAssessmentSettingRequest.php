<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateAssessmentSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->hasPermission('assessment_settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'min_grade'         => ['numeric', 'min:0', 'max:20'],
            'max_grade'         => ['numeric', 'min:0', 'max:100'],
            'passing_grade'     => ['numeric', 'min:0', 'max:20'],
            'active_components' => ['array'],
            'active_components.*'=> ['string', 'in:AC,PP,PT'],
            'nf_formula'        => ['string', 'max:200'],
            'mfa_formula'       => ['string', 'max:200'],
            'rounding_mode'     => ['string', 'in:half_up,half_down,half_even'],
            'pp_active'         => ['boolean'],
        ];
    }
}
