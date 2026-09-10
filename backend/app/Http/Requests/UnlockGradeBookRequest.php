<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UnlockGradeBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->hasPermission('grade_books.unlock') ?? false;
    }

    public function rules(): array
    {
        return [
            'unlock_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'unlock_reason.required' => 'É obrigatório indicar o motivo do desbloqueio da pauta.',
            'unlock_reason.min'      => 'O motivo deve ter pelo menos 10 caracteres.',
        ];
    }
}
