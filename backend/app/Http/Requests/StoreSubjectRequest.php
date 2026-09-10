<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('subjects')->where(function ($query) {
                    return $query->where('school_id', request()->user()->school_id ?? request()->input('school_id'));
                }),
            ],
            'description' => 'nullable|string',
            'weekly_hours' => 'integer|min:0|max:40',
            'is_active' => 'boolean',
        ];
    }
}
