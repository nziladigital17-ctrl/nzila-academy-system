<?php

namespace App\Services;

use App\Enums\AcademicSituationEnum;
use App\Enums\AlertTypeEnum;
use App\Enums\AttendancePolicyModeEnum;
use App\Enums\AttendanceStatusEnum;
use App\Models\AcademicAlert;
use App\Models\AttendancePolicySetting;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\TermResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    // ── Session recording ─────────────────────────────────────────────────────

    /**
     * Create an attendance session with records for all enrolled students.
     * If students array is empty, creates records defaulting to 'P'.
     */
    public function recordSession(array $data, array $records = []): AttendanceSession
    {
        return DB::transaction(function () use ($data, $records) {
            $session = AttendanceSession::create(array_merge($data, [
                'recorded_by' => Auth::id(),
            ]));

            // Get enrolled students if no records provided
            if (empty($records)) {
                $enrollments = Enrollment::where('class_id', $data['class_id'])
                    ->where('status', 'active')
                    ->get();

                foreach ($enrollments as $enrollment) {
                    AttendanceRecord::create([
                        'attendance_session_id' => $session->id,
                        'enrollment_id'         => $enrollment->id,
                        'status'                => AttendanceStatusEnum::PRESENT->value,
                    ]);
                }
            } else {
                foreach ($records as $record) {
                    AttendanceRecord::create([
                        'attendance_session_id' => $session->id,
                        'enrollment_id'         => $record['enrollment_id'],
                        'status'                => $record['status'],
                        'justification'         => $record['justification'] ?? null,
                    ]);
                }
            }

            return $session->load('attendanceRecords');
        });
    }

    /**
     * Update a single attendance record (P/F/J).
     */
    public function updateRecord(AttendanceRecord $record, string $status, ?string $justification = null): AttendanceRecord
    {
        $record->update([
            'status'        => $status,
            'justification' => $justification,
        ]);

        // Recalculate alerts after update
        $this->checkAttendanceLimits(
            $record->enrollment,
            $record->attendanceSession->subject_id,
            $record->attendanceSession->term_id,
            $record->attendanceSession->academic_year_id
        );

        return $record->fresh();
    }

    // ── Summary calculation ───────────────────────────────────────────────────

    /**
     * Calculate attendance summary for a student in a subject/term.
     */
    public function calculateSummary(Enrollment $enrollment, int $subjectId, int $termId): array
    {
        $records = AttendanceRecord::whereHas('attendanceSession', function ($q) use ($subjectId, $termId) {
            $q->where('subject_id', $subjectId)
              ->where('term_id', $termId);
        })
        ->where('enrollment_id', $enrollment->id)
        ->get();

        $total             = $records->count();
        $presences         = $records->where('status', AttendanceStatusEnum::PRESENT)->count();
        $absencesJustified = $records->where('status', AttendanceStatusEnum::ABSENT_JUSTIFIED)->count();
        $absencesUnjustified = $records->where('status', AttendanceStatusEnum::ABSENT_UNJUSTIFIED)->count();
        $totalAbsences     = $absencesJustified + $absencesUnjustified;
        $percentage        = $total > 0 ? round(($presences / $total) * 100, 2) : null;

        return [
            'total_sessions'         => $total,
            'presences'              => $presences,
            'absences_justified'     => $absencesJustified,
            'absences_unjustified'   => $absencesUnjustified,
            'total_absences'         => $totalAbsences,
            'attendance_percentage'  => $percentage,
        ];
    }

    // ── Limit checking ────────────────────────────────────────────────────────

    /**
     * Check if student is approaching or exceeding the absence limit.
     * Returns 'ok', 'warning', or 'exceeded'.
     */
    public function checkLimits(
        Enrollment $enrollment,
        int $subjectId,
        int $termId,
        AttendancePolicySetting $policy,
        int $weeklyPeriods = 1
    ): string {
        $summary = $this->calculateSummary($enrollment, $subjectId, $termId);

        $relevantAbsences = $policy->countsJustified()
            ? $summary['total_absences']
            : $summary['absences_unjustified'];

        $limit = $this->getLimit($policy, $weeklyPeriods, $subjectId, $termId);

        if ($limit === null) {
            return 'ok';
        }

        $threshold = $limit * $policy->alert_threshold;

        if ($relevantAbsences >= $limit) {
            return 'exceeded';
        }

        if ($relevantAbsences >= $threshold) {
            return 'warning';
        }

        return 'ok';
    }

    /**
     * Check attendance limits and generate alerts as needed.
     */
    public function checkAttendanceLimits(
        Enrollment $enrollment,
        int $subjectId,
        int $termId,
        int $academicYearId
    ): void {
        $policy = AttendancePolicySetting::where('school_id', $enrollment->school_id)
            ->where('academic_year_id', $academicYearId)
            ->first();

        if (!$policy) {
            return;
        }

        // Get weekly periods from the first session for this subject/class/term
        $firstSession = AttendanceSession::where('class_id', $enrollment->class_id)
            ->where('subject_id', $subjectId)
            ->where('term_id', $termId)
            ->first();

        $weeklyPeriods = $firstSession?->weekly_periods ?? 1;
        $status = $this->checkLimits($enrollment, $subjectId, $termId, $policy, $weeklyPeriods);

        if ($status === 'warning' || $status === 'exceeded') {
            $summary = $this->calculateSummary($enrollment, $subjectId, $termId);

            AcademicAlert::updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'subject_id'    => $subjectId,
                    'term_id'       => $termId,
                    'type'          => AlertTypeEnum::ATTENDANCE_RISK->value,
                ],
                [
                    'school_id'        => $enrollment->school_id,
                    'academic_year_id' => $academicYearId,
                    'is_acknowledged'  => false,
                    'context'          => [
                        'absences_unjustified' => $summary['absences_unjustified'],
                        'absences_justified'   => $summary['absences_justified'],
                        'limit'                => $this->getLimit($policy, $weeklyPeriods, $subjectId, $termId),
                        'weekly_periods'       => $weeklyPeriods,
                        'status'               => $status,
                    ],
                ]
            );

            // Update term result attendance situation
            TermResult::where('enrollment_id', $enrollment->id)
                ->where('subject_id', $subjectId)
                ->where('term_id', $termId)
                ->update([
                    'situation' => $status === 'exceeded'
                        ? AcademicSituationEnum::EM_RISCO_POR_FALTAS->value
                        : AcademicSituationEnum::EM_RISCO_POR_FALTAS->value,
                ]);
        }
    }

    /**
     * Update term result with attendance counts.
     */
    public function updateTermResultAttendance(Enrollment $enrollment, int $subjectId, int $termId): void
    {
        $summary = $this->calculateSummary($enrollment, $subjectId, $termId);

        TermResult::where('enrollment_id', $enrollment->id)
            ->where('subject_id', $subjectId)
            ->where('term_id', $termId)
            ->update([
                'presences'             => $summary['presences'],
                'absences_justified'    => $summary['absences_justified'],
                'absences_unjustified'  => $summary['absences_unjustified'],
                'attendance_percentage' => $summary['attendance_percentage'],
                'calculated_at'         => now(),
            ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function getLimit(
        AttendancePolicySetting $policy,
        int $weeklyPeriods,
        int $subjectId,
        int $termId
    ): ?int {
        if ($policy->mode === AttendancePolicyModeEnum::ANGOLA_POR_DISCIPLINA) {
            return $policy->getAngolaLimit($weeklyPeriods);
        }

        // Custom mode
        $custom = $policy->custom_settings ?? [];
        return isset($custom['global_limit']) ? (int) $custom['global_limit'] : null;
    }
}
