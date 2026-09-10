<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceSummaryController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    /**
     * Get attendance summary with flexible filters.
     * Filterable by: enrollment, subject, term, class, student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Single student/subject/term summary
        if ($request->enrollment_id && $request->subject_id && $request->term_id) {
            $enrollment = Enrollment::findOrFail($request->enrollment_id);

            // Students can only view their own
            if ($user->hasRole('student') && $user->student?->id !== $enrollment->student_id) {
                abort(403);
            }

            $summary = $this->attendanceService->calculateSummary(
                $enrollment,
                $request->subject_id,
                $request->term_id
            );

            return response()->json([
                'message' => 'Resumo de frequência obtido com sucesso.',
                'data'    => $summary,
            ]);
        }

        // Class-level summary: all students, one subject, one term
        if ($request->class_id && $request->subject_id && $request->term_id) {
            $enrollments = Enrollment::with('student')
                ->where('class_id', $request->class_id)
                ->where('status', 'active')
                ->get();

            $results = $enrollments->map(function ($enrollment) use ($request) {
                $summary = $this->attendanceService->calculateSummary(
                    $enrollment,
                    $request->subject_id,
                    $request->term_id
                );
                return array_merge([
                    'enrollment_id' => $enrollment->id,
                    'student'       => [
                        'id'   => $enrollment->student?->id,
                        'name' => $enrollment->student?->full_name,
                        'number' => $enrollment->student?->student_number,
                    ],
                ], $summary);
            });

            return response()->json([
                'message' => 'Resumo de frequência da turma obtido com sucesso.',
                'data'    => $results,
            ]);
        }

        return response()->json([
            'message' => 'Forneça enrollment_id + subject_id + term_id ou class_id + subject_id + term_id.',
        ], 422);
    }
}
