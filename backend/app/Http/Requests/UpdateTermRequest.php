<?php

namespace App\Http\Requests;

use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('terms')->where(function ($query) {
                    $term = $this->route('term');
                    return $query->where('academic_year_id', $term?->academic_year_id ?? request()->input('academic_year_id'));
                })->ignore($this->route('term')?->id)
            ],
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
        ];
    }

    /**
     * Validate date overlap on update.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $term = $this->route('term');
            if (!$term) {
                return;
            }

            $startDate = $this->input('start_date', $term->start_date?->format('Y-m-d'));
            $endDate = $this->input('end_date', $term->end_date?->format('Y-m-d'));

            if (!$startDate || !$endDate) {
                return;
            }

            $overlapping = Term::where('academic_year_id', $term->academic_year_id)
                ->where('id', '!=', $term->id)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->where('start_date', '<=', $endDate)
                          ->where('end_date', '>=', $startDate);
                })
                ->exists();

            if ($overlapping) {
                $validator->errors()->add('start_date', 'As datas do trimestre sobrepõem-se a outro trimestre existente.');
            }
        });
    }
}
