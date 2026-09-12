<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpsertFinancialSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'required|exists:academic_years,id',
            'settings' => 'required|array',
            'settings.*.setting_key' => 'required|string|max:100',
            'settings.*.setting_value' => 'nullable|string|max:1000',
        ];
    }
}


