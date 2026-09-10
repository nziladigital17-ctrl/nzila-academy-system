<?php

namespace App\Http\Requests;

use App\Enums\AssessmentTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->hasPermission('grades.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'label'     => ['nullable', 'string', 'max:100'],
            'date'      => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }
}
