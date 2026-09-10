<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'sometimes|string|max:255',
            'gender' => 'sometimes|in:M,F',
            'birth_date' => 'sometimes|date',
            'birth_place' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:100',
            'bi_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
