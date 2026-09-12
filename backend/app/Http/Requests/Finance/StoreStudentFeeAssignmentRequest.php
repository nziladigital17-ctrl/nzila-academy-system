<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentFeeAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'tuition_plan_id' => 'required|exists:tuition_plans,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_fixed' => 'nullable|numeric|min:0',
            'scholarship_type' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
