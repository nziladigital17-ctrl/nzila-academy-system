<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subject = $this->route('subject');

        return [
            'name' => 'sometimes|string|max:255',
            'code' => [
                'sometimes',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('subjects')->where(function ($query) use ($subject) {
                    return $query->where('school_id', $subject?->school_id ?? request()->user()->school_id);
                })->ignore($subject?->id),
            ],
            'description' => 'nullable|string',
            'weekly_hours' => 'integer|min:0|max:40',
            'is_active' => 'boolean',
        ];
    }
}
