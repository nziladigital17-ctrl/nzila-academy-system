<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'employee_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('teachers')->where(function ($query) {
                    return $query->where('school_id', $this->teacher->school_id);
                })->ignore($this->teacher->id),
            ],
            'full_name' => 'sometimes|required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'academic_degree' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'hire_date' => 'sometimes|required|date',
            'is_active' => 'boolean',
        ];
    }
}
