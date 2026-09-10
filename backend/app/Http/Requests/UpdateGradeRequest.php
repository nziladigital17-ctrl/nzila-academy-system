<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->hasPermission('grades.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'score'   => ['required', 'numeric', 'min:0', 'max:20'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'score.min' => 'A nota não pode ser inferior a 0.',
            'score.max' => 'A nota não pode ser superior a 20.',
        ];
    }
}
