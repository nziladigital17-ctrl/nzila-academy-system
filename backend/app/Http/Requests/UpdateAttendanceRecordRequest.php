<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->hasPermission('attendance.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'status'        => ['required', Rule::in(AttendanceStatusEnum::values())],
            'justification' => [
                'nullable',
                'string',
                'max:500',
                // Justification required when status is J
                Rule::requiredIf(fn() => $this->status === AttendanceStatusEnum::ABSENT_JUSTIFIED->value),
            ],
        ];
    }
}
