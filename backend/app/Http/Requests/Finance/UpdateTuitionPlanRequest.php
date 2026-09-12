<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTuitionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50',
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'grade_level' => 'sometimes|string|max:50',
            'class_id' => 'sometimes|nullable|exists:classes,id',
            'student_id' => 'sometimes|nullable|exists:students,id',
            'amount' => 'sometimes|numeric|min:0',
            'installments' => 'sometimes|nullable|integer|min:1|max:12',
            'periodicity' => 'sometimes|in:enrollment,monthly,quarterly,annual,one_time',
            'due_day' => 'sometimes|nullable|integer|min:1|max:28',
            'is_active' => 'sometimes|nullable|boolean',
        ];
    }
}
