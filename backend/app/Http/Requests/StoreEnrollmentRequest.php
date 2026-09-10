<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'exists:students,id',
                function ($attribute, $value, $fail) {
                    $student = Student::withoutGlobalScopes()->find($value);
                    $user = $this->user();
                    if ($student && $user->school_id && $student->school_id !== $user->school_id) {
                        $fail('O aluno não pertence à sua escola.');
                    }
                },
            ],
            'class_id' => [
                'required',
                'exists:classes,id',
                function ($attribute, $value, $fail) {
                    $class = SchoolClass::withoutGlobalScopes()->find($value);
                    $user = $this->user();
                    if ($class && $user->school_id && $class->school_id !== $user->school_id) {
                        $fail('A turma não pertence à sua escola.');
                    }
                },
            ],
        ];
    }
}
