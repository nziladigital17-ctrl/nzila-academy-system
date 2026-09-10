<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'school_id'             => $this->school_id,
            'academic_year_id'      => $this->academic_year_id,
            'term_id'               => $this->term_id,
            'class_id'              => $this->class_id,
            'subject_id'            => $this->subject_id,
            'teacher_assignment_id' => $this->teacher_assignment_id,
            'date'                  => $this->date?->format('Y-m-d'),
            'weekly_periods'        => $this->weekly_periods,
            'notes'                 => $this->notes,
            'subject'               => $this->whenLoaded('subject', fn() => [
                'id'   => $this->subject->id,
                'name' => $this->subject->name,
            ]),
            'class'                 => $this->whenLoaded('schoolClass', fn() => [
                'id'   => $this->schoolClass->id,
                'name' => $this->schoolClass->name,
            ]),
            'term'                  => $this->whenLoaded('term', fn() => [
                'id'   => $this->term->id,
                'name' => $this->term->name,
            ]),
            'records'               => AttendanceRecordResource::collection($this->whenLoaded('attendanceRecords')),
            'records_count'         => $this->whenLoaded(
                'attendanceRecords',
                fn() => $this->attendanceRecords->count()
            ),
            'recorded_by'           => $this->whenLoaded('recordedByUser', fn() => [
                'id'   => $this->recordedByUser->id,
                'name' => $this->recordedByUser->name,
            ]),
            'created_at'            => $this->created_at?->toIso8601String(),
        ];
    }
}
