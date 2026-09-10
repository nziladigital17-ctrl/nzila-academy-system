<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'sometimes',
                'required',
                'exists:academic_years,id',
                function ($attribute, $value, $fail) {
                    $year = \App\Models\AcademicYear::find($value);
                    if ($year && request()->user()->school_id && $year->school_id !== request()->user()->school_id) {
                        $fail('The selected academic year is invalid.');
                    }
                }
            ],
            'subject_id' => 'nullable|exists:subjects,id',
            'room_id' => 'nullable|exists:rooms,id',
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('classes')->where(function ($query) {
                    return $query->where('academic_year_id', request()->input('academic_year_id', $this->class->academic_year_id))
                        ->where('school_id', $this->class->school_id);
                })->ignore($this->class->id)
            ],
            'grade_level' => 'sometimes|required|string|max:255',
            'shift' => 'sometimes|required|in:morning,afternoon,evening',
            'max_students' => 'integer|min:1|max:100',
        ];
    }
}
