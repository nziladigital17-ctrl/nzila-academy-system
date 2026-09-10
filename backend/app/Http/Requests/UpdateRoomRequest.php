<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
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
                'sometimes',
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('rooms')->where(function ($query) {
                    return $query->where('school_id', $this->room->school_id);
                })->ignore($this->room->id),
            ],
            'capacity' => 'nullable|integer|min:1',
            'building' => 'nullable|string|max:255',
        ];
    }
}
