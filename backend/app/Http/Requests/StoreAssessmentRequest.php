<?php

namespace App\Http\Requests;

use App\Enums\AssessmentTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->hasPermission('grades.create') ?? false;
    }

    public function rules(): array
    {
        $schoolId = Auth::user()?->school_id;

        return [
            'academic_year_id'       => ['required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'term_id'                => ['required', 'integer', Rule::exists('terms', 'id')],
            'class_id'               => ['required', 'integer', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
            'subject_id'             => ['required', 'integer', Rule::exists('subjects', 'id')->where('school_id', $schoolId)],
            'teacher_assignment_id'  => ['nullable', 'integer', 'exists:teacher_assignments,id'],
            'type'                   => ['required', Rule::in(AssessmentTypeEnum::values())],
            'label'                  => ['nullable', 'string', 'max:100'],
            'date'                   => ['nullable', 'date'],
            'is_active'              => ['boolean'],
        ];
    }
}
