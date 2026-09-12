<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:discount,scholarship,exemption,penalty,correction,refund',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
        ];
    }
}
