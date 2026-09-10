<?php

namespace App\Services;

use App\Models\AcademicAlert;
use App\Models\AnnualResult;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\TermResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AcademicReportService
{
    public function __construct(
        private readonly GradingService $gradingService
    ) {}

    // ── 1. Student Report Card (Boletim Individual) ───────────────────────────

    /**
     * Generate individual student report card for a given term.
     * Returns structured data ready for JSON serialisation.
     */
    public function studentReportCard(int $enrollmentId, int $termId): array
    {
        $enrollment = Enrollment::with([
            'student',
            'schoolClass.academicYear',
            'schoolClass.subject',
        ])->findOrFail($enrollmentId);

        $term = Term::with('academicYear')->findOrFail($termId);

        // Get all subjects for the class (via teacher assignments)
        $subjects = \App\Models\Subject::whereHas('teacherAssignments', function ($q) use ($enrollment, $termId) {
            $q->where('class_id', $enrollment->class_id)
              ->where('academic_year_id', $enrollment->schoolClass->academic_year_id);
        })->get();

        $settings = \App\Models\AssessmentSetting::where('school_id', $enrollment->school_id)
            ->where('academic_year_id', $enrollment->schoolClass->academic_year_id)
            ->first();

        $subjectReports = [];
        foreach ($subjects as $subject) {
            $termResult = TermResult::where('enrollment_id', $enrollmentId)
                ->where('subject_id', $subject->id)
                ->where('term_id', $termId)
                ->first();

            // Get individual AC grades
            $acGrades = \App\Models\Grade::whereHas('assessment', function ($q) use ($subject, $termId) {
                $q->where('subject_id', $subject->id)
                  ->where('term_id', $termId)
                  ->where('type', 'AC')
                  ->where('is_active', true);
            })
            ->where('enrollment_id', $enrollmentId)
            ->where('is_annulled', false)
            ->with('assessment')
            ->get()
            ->map(fn($g) => [
                'label' => $g->assessment->label ?? 'AC',
                'score' => (float) $g->score,
                'date'  => $g->assessment->date?->format('Y-m-d'),
            ]);

            // Alerts for this subject/term
            $alerts = AcademicAlert::where('enrollment_id', $enrollmentId)
                ->where('subject_id', $subject->id)
                ->where('term_id', $termId)
                ->where('is_acknowledged', false)
                ->get()
                ->map(fn($a) => [
                    'type'    => $a->type->value,
                    'context' => $a->context,
                ]);

            $roundedNf = $termResult && $termResult->nf !== null
                ? ($settings ? $this->gradingService->roundGrade((float)$termResult->nf, $settings->rounding_mode) : round((float)$termResult->nf))
                : null;

            $subjectReports[] = [
                'subject'             => ['id' => $subject->id, 'name' => $subject->name, 'code' => $subject->code],
                'ac_grades'           => $acGrades,
                'mac'                 => $termResult?->mac !== null ? round((float)$termResult->mac, 2) : null,
                'pp'                  => $termResult?->pp !== null ? round((float)$termResult->pp, 2) : null,
                'pt'                  => $termResult?->pt !== null ? round((float)$termResult->pt, 2) : null,
                'nf_raw'              => $termResult?->nf !== null ? round((float)$termResult->nf, 2) : null,
                'nf'                  => $roundedNf,
                'presences'           => $termResult?->presences ?? 0,
                'absences_justified'  => $termResult?->absences_justified ?? 0,
                'absences_unjustified'=> $termResult?->absences_unjustified ?? 0,
                'total_absences'      => ($termResult?->absences_justified ?? 0) + ($termResult?->absences_unjustified ?? 0),
                'attendance_percentage' => $termResult?->attendance_percentage,
                'situation'           => $termResult?->situation?->value ?? 'sem_notas',
                'alerts'              => $alerts,
            ];
        }

        return [
            'student'      => [
                'id'             => $enrollment->student->id,
                'name'           => $enrollment->student->full_name,
                'student_number' => $enrollment->student->student_number,
            ],
            'class'        => [
                'id'   => $enrollment->schoolClass->id,
                'name' => $enrollment->schoolClass->name,
            ],
            'academic_year'=> [
                'id'   => $term->academicYear->id,
                'name' => $term->academicYear->name,
            ],
            'term'         => [
                'id'         => $term->id,
                'name'       => $term->name,
                'start_date' => $term->start_date->format('Y-m-d'),
                'end_date'   => $term->end_date->format('Y-m-d'),
            ],
            'subjects'     => $subjectReports,
        ];
    }

    // ── 2. Class Grade Sheet (Pauta Geral da Turma) ───────────────────────────

    /**
     * Generate full class grade sheet for a term.
     */
    public function classGradeSheet(int $classId, int $termId): array
    {
        $class = SchoolClass::with('academicYear')->findOrFail($classId);
        $term  = Term::findOrFail($termId);

        $subjects = \App\Models\Subject::whereHas('teacherAssignments', function ($q) use ($classId) {
            $q->where('class_id', $classId);
        })->get();

        $enrollments = Enrollment::with('student')
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->get();

        $studentRows = [];
        foreach ($enrollments as $enrollment) {
            $subjectResults = [];
            foreach ($subjects as $subject) {
                $tr = TermResult::where('enrollment_id', $enrollment->id)
                    ->where('subject_id', $subject->id)
                    ->where('term_id', $termId)
                    ->first();

                $subjectResults[$subject->id] = [
                    'mac'                  => $tr?->mac !== null ? round((float)$tr->mac, 2) : null,
                    'pp'                   => $tr?->pp !== null ? round((float)$tr->pp, 2) : null,
                    'pt'                   => $tr?->pt !== null ? round((float)$tr->pt, 2) : null,
                    'nf'                   => $tr?->nf !== null ? (int) round((float)$tr->nf, 0, PHP_ROUND_HALF_UP) : null,
                    'situation'            => $tr?->situation?->value ?? 'sem_notas',
                    'presences'            => $tr?->presences ?? 0,
                    'absences_unjustified' => $tr?->absences_unjustified ?? 0,
                ];
            }

            $studentRows[] = [
                'enrollment_id'  => $enrollment->id,
                'student'        => [
                    'id'             => $enrollment->student->id,
                    'name'           => $enrollment->student->full_name,
                    'student_number' => $enrollment->student->student_number,
                ],
                'subjects'       => $subjectResults,
            ];
        }

        return [
            'class'        => ['id' => $class->id, 'name' => $class->name],
            'academic_year'=> ['id' => $class->academicYear->id, 'name' => $class->academicYear->name],
            'term'         => ['id' => $term->id, 'name' => $term->name],
            'subjects'     => $subjects->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code]),
            'students'     => $studentRows,
        ];
    }

    // ── 3. Subject Mini Grade Sheet ───────────────────────────────────────────

    /**
     * Generate mini grade sheet for one subject in a class/term.
     */
    public function subjectMiniGradeSheet(int $classId, int $subjectId, int $termId): array
    {
        $class   = SchoolClass::with('academicYear')->findOrFail($classId);
        $subject = \App\Models\Subject::findOrFail($subjectId);
        $term    = Term::findOrFail($termId);

        $enrollments = Enrollment::with('student')
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->get();

        $rows = [];
        foreach ($enrollments as $enrollment) {
            // Individual AC grades
            $acGrades = \App\Models\Grade::whereHas('assessment', function ($q) use ($subjectId, $termId) {
                $q->where('subject_id', $subjectId)
                  ->where('term_id', $termId)
                  ->where('type', 'AC')
                  ->where('is_active', true);
            })
            ->where('enrollment_id', $enrollment->id)
            ->where('is_annulled', false)
            ->with('assessment')
            ->get()
            ->map(fn($g) => [
                'label' => $g->assessment->label ?? 'AC',
                'score' => (float) $g->score,
            ]);

            $tr = TermResult::where('enrollment_id', $enrollment->id)
                ->where('subject_id', $subjectId)
                ->where('term_id', $termId)
                ->first();

            $rows[] = [
                'student'    => [
                    'id'             => $enrollment->student->id,
                    'name'           => $enrollment->student->full_name,
                    'student_number' => $enrollment->student->student_number,
                ],
                'ac_grades'  => $acGrades,
                'mac'        => $tr?->mac !== null ? round((float)$tr->mac, 2) : null,
                'pp'         => $tr?->pp !== null ? round((float)$tr->pp, 2) : null,
                'pt'         => $tr?->pt !== null ? round((float)$tr->pt, 2) : null,
                'nf'         => $tr?->nf !== null ? (int) round((float)$tr->nf, 0, PHP_ROUND_HALF_UP) : null,
                'situation'  => $tr?->situation?->value ?? 'sem_notas',
            ];
        }

        return [
            'class'        => ['id' => $class->id, 'name' => $class->name],
            'academic_year'=> ['id' => $class->academicYear->id, 'name' => $class->academicYear->name],
            'subject'      => ['id' => $subject->id, 'name' => $subject->name, 'code' => $subject->code],
            'term'         => ['id' => $term->id, 'name' => $term->name],
            'students'     => $rows,
        ];
    }

    // ── 4. Academic Alerts Report ─────────────────────────────────────────────

    /**
     * Generate academic alerts report with filters.
     */
    public function academicAlerts(array $filters = []): array
    {
        $query = AcademicAlert::with([
            'enrollment.student',
            'enrollment.schoolClass',
            'subject',
            'term',
        ]);

        if (!empty($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }
        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        if (!empty($filters['term_id'])) {
            $query->where('term_id', $filters['term_id']);
        }
        if (!empty($filters['class_id'])) {
            $query->whereHas('enrollment', fn($q) => $q->where('class_id', $filters['class_id']));
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['acknowledged'])) {
            $query->where('is_acknowledged', $filters['acknowledged']);
        }

        $alerts = $query->orderBy('created_at', 'desc')->get();

        return [
            'total'            => $alerts->count(),
            'academic_risk'    => $alerts->where('type.value', 'academic_risk')->count(),
            'attendance_risk'  => $alerts->where('type.value', 'attendance_risk')->count(),
            'alerts'           => $alerts->map(fn($alert) => [
                'id'           => $alert->id,
                'type'         => $alert->type->value,
                'type_label'   => $alert->type->displayName(),
                'student'      => [
                    'id'   => $alert->enrollment?->student?->id,
                    'name' => $alert->enrollment?->student?->full_name,
                ],
                'class'        => [
                    'id'   => $alert->enrollment?->schoolClass?->id,
                    'name' => $alert->enrollment?->schoolClass?->name,
                ],
                'subject'      => $alert->subject ? ['id' => $alert->subject->id, 'name' => $alert->subject->name] : null,
                'term'         => $alert->term ? ['id' => $alert->term->id, 'name' => $alert->term->name] : null,
                'context'      => $alert->context,
                'acknowledged' => $alert->is_acknowledged,
                'created_at'   => $alert->created_at->toIso8601String(),
            ])->values(),
        ];
    }
}
