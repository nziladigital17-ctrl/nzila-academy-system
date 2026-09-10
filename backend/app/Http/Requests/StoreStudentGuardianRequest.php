<?php

namespace App\Http\Requests;

use App\Models\Guardian;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guardian_id' => [
                'required',
                'exists:guardians,id',
                function ($attribute, $value, $fail) {
                    $guardian = Guardian::withoutGlobalScopes()->find($value);
                    $student = $this->route('student');
                    if ($guardian && $student && $guardian->school_id !== $student->school_id) {
                        $fail('O encarregado não pertence à mesma escola do aluno.');
                    }
                },
            ],
            'relationship' => 'required|in:pai,mae,tutor,outro',
            'is_primary' => 'sometimes|boolean',
        ];
    }
}
