<?php

namespace App\Http\Requests;

use App\Models\TeacherAssignment;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => [
                'required',
                'exists:teachers,id',
                function ($attribute, $value, $fail) {
                    $teacher = \App\Models\Teacher::find($value);
                    if ($teacher && request()->user()->school_id && $teacher->school_id !== request()->user()->school_id) {
                        $fail('O professor selecionado não pertence à sua escola.');
                    }
                }
            ],
            'class_id' => [
                'required',
                'exists:classes,id',
                function ($attribute, $value, $fail) {
                    $class = \App\Models\SchoolClass::find($value);
                    if ($class && request()->user()->school_id && $class->school_id !== request()->user()->school_id) {
                        $fail('A turma selecionada não pertence à sua escola.');
                    }
                }
            ],
            'subject_id' => [
                'required',
                'exists:subjects,id',
                function ($attribute, $value, $fail) {
                    $subject = \App\Models\Subject::find($value);
                    if ($subject && request()->user()->school_id && $subject->school_id !== request()->user()->school_id) {
                        $fail('A disciplina selecionada não pertence à sua escola.');
                    }
                }
            ],
            'academic_year_id' => [
                'required',
                'exists:academic_years,id',
                function ($attribute, $value, $fail) {
                    $year = \App\Models\AcademicYear::find($value);
                    if ($year && request()->user()->school_id && $year->school_id !== request()->user()->school_id) {
                        $fail('O ano lectivo selecionado não pertence à sua escola.');
                    }
                }
            ],
            'role' => 'required|in:titular,auxiliary',
        ];
    }

    /**
     * Validate no duplicate assignment.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $exists = TeacherAssignment::where('teacher_id', $this->input('teacher_id'))
                ->where('class_id', $this->input('class_id'))
                ->where('subject_id', $this->input('subject_id'))
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('teacher_id', 'Esta associação professor-turma-disciplina já existe neste ano lectivo.');
            }
        });
    }
}
