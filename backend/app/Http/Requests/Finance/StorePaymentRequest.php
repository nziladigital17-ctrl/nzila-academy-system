<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer,multicaixa,other',
            'reference' => 'nullable|string|max:255',
            'paid_at' => 'nullable|date',
            'invoice_id' => 'required_without:allocations|exists:invoices,id',
            'allocations' => 'nullable|array',
            'allocations.*.invoice_id' => 'required|exists:invoices,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $allocations = $this->input('allocations');
            $amount = $this->input('amount');

            if (is_array($allocations) && count($allocations) > 0) {
                $totalAllocated = array_sum(array_column($allocations, 'amount'));
                if (round((float) $totalAllocated, 2) !== round((float) $amount, 2)) {
                    $validator->errors()->add('allocations', 'The sum of allocation amounts must equal the total payment amount.');
                }
            }
        });
    }
}
