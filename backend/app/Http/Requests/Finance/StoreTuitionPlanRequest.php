<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreTuitionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade_level' => 'required|string|max:50',
            'class_id' => 'nullable|exists:classes,id',
            'student_id' => 'nullable|exists:students,id',
            'amount' => 'required|numeric|min:0',
            'installments' => 'nullable|integer|min:1|max:12',
            'periodicity' => 'required|in:enrollment,monthly,quarterly,annual,one_time',
            'due_day' => 'nullable|integer|min:1|max:28',
            'is_active' => 'nullable|boolean',
        ];
    }
}
