<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'employee_number' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('teachers')->where(function ($query) {
                    return $query->where('school_id', request()->user()->school_id ?? request()->input('school_id'));
                }),
            ],
            'full_name' => 'required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'academic_degree' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'hire_date' => 'required|date',
            'is_active' => 'boolean',
        ];
    }
}
