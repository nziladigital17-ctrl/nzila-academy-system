<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academicYear = $this->route('academicYear');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('academic_years')->where(function ($query) use ($academicYear) {
                    return $query->where('school_id', $academicYear?->school_id ?? request()->user()->school_id);
                })->ignore($academicYear?->id),
            ],
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'is_current' => 'boolean',
        ];
    }
}
