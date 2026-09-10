<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreAttendanceSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->hasPermission('attendance.create') ?? false;
    }

    public function rules(): array
    {
        $schoolId = Auth::user()?->school_id;

        return [
            'academic_year_id'      => ['required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'term_id'               => ['required', 'integer', 'exists:terms,id'],
            'class_id'              => ['required', 'integer', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
            'subject_id'            => ['required', 'integer', Rule::exists('subjects', 'id')->where('school_id', $schoolId)],
            'teacher_assignment_id' => ['nullable', 'integer', 'exists:teacher_assignments,id'],
            'date'                  => ['required', 'date'],
            'weekly_periods'        => ['required', 'integer', 'min:1', 'max:10'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
            'records'               => ['nullable', 'array'],
            'records.*.enrollment_id' => ['required_with:records', 'integer', 'exists:enrollments,id'],
            'records.*.status'       => ['required_with:records', Rule::in(AttendanceStatusEnum::values())],
            'records.*.justification'=> ['nullable', 'string', 'max:500'],
        ];
    }
}
