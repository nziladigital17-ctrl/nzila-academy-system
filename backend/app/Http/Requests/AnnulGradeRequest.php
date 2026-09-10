<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AnnulGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->hasPermission('grades.annul') ?? false;
    }

    public function rules(): array
    {
        return [
            'annulled_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'annulled_reason.required' => 'É obrigatório indicar o motivo da anulação.',
            'annulled_reason.min'      => 'O motivo da anulação deve ter pelo menos 10 caracteres.',
        ];
    }
}
