<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AcademicReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct(private readonly AcademicReportService $reportService) {}

    /**
     * GET /api/v1/reports/student-report-card
     * Required: enrollment_id, term_id
     */
    public function studentReportCard(Request $request): JsonResponse
    {
        $request->validate([
            'enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'term_id'       => ['required', 'integer', 'exists:terms,id'],
        ]);

        $user = Auth::user();

        // Students can only view their own report card
        if ($user->hasRole('student') && $user->student) {
            $enrollment = \App\Models\Enrollment::find($request->enrollment_id);
            if ($enrollment?->student_id !== $user->student->id) {
                abort(403, 'Não tem acesso a este boletim.');
            }
        }

        $data = $this->reportService->studentReportCard(
            $request->enrollment_id,
            $request->term_id
        );

        return response()->json([
            'message' => 'Boletim individual gerado com sucesso.',
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/v1/reports/class-grade-sheet
     * Required: class_id, term_id
     */
    public function classGradeSheet(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'term_id'  => ['required', 'integer', 'exists:terms,id'],
        ]);

        $data = $this->reportService->classGradeSheet(
            $request->class_id,
            $request->term_id
        );

        return response()->json([
            'message' => 'Pauta geral da turma gerada com sucesso.',
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/v1/reports/subject-mini-grade-sheet
     * Required: class_id, subject_id, term_id
     */
    public function subjectMiniGradeSheet(Request $request): JsonResponse
    {
        $request->validate([
            'class_id'   => ['required', 'integer', 'exists:classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'term_id'    => ['required', 'integer', 'exists:terms,id'],
        ]);

        $data = $this->reportService->subjectMiniGradeSheet(
            $request->class_id,
            $request->subject_id,
            $request->term_id
        );

        return response()->json([
            'message' => 'Mini pauta por disciplina gerada com sucesso.',
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/v1/reports/academic-alerts
     * Optional filters: class_id, subject_id, term_id, academic_year_id, type
     */
    public function academicAlerts(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['nullable', 'string', 'in:academic_risk,attendance_risk'],
        ]);

        $filters = array_merge(
            $request->only(['class_id', 'subject_id', 'term_id', 'academic_year_id', 'type']),
            ['school_id' => Auth::user()->school_id]
        );

        if ($request->has('acknowledged')) {
            $filters['acknowledged'] = $request->boolean('acknowledged');
        }

        $data = $this->reportService->academicAlerts($filters);

        return response()->json([
            'message' => 'Relatório de alertas gerado com sucesso.',
            'data'    => $data,
        ]);
    }
}
