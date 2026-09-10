<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('rooms')->where(function ($query) {
                    return $query->where('school_id', request()->user()->school_id ?? request()->input('school_id'));
                }),
            ],
            'capacity' => 'nullable|integer|min:1',
            'building' => 'nullable|string|max:255',
        ];
    }
}
