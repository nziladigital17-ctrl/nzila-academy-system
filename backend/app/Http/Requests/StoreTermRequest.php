<?php

namespace App\Http\Requests;

use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;

class StoreTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'required',
                'exists:academic_years,id',
                function ($attribute, $value, $fail) {
                    $year = \App\Models\AcademicYear::find($value);
                    if ($year && request()->user()->school_id && $year->school_id !== request()->user()->school_id) {
                        $fail('The selected academic year is invalid.');
                    }
                }
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('terms')->where(function ($query) {
                    return $query->where('academic_year_id', request()->input('academic_year_id'));
                })
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ];
    }

    /**
     * Additional validation after rules pass.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $academicYearId = $this->input('academic_year_id');
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');

            // Check for overlapping terms in the same academic year
            $overlapping = Term::where('academic_year_id', $academicYearId)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $endDate)
                          ->where('end_date', '>=', $startDate);
                    });
                })
                ->exists();

            if ($overlapping) {
                $validator->errors()->add('start_date', 'As datas do trimestre sobrepõem-se a outro trimestre existente.');
            }
        });
    }
}
