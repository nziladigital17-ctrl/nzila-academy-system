<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnualResultResource;
use App\Http\Resources\TermResultResource;
use App\Models\AnnualResult;
use App\Models\TermResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademicResultController extends Controller
{
    /**
     * List academic results (term or annual) with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $type = $request->get('type', 'term'); // term | annual

        if ($type === 'annual') {
            return $this->annualResults($request, $user);
        }

        return $this->termResults($request, $user);
    }

    private function termResults(Request $request, $user): JsonResponse
    {
        $query = TermResult::with(['subject', 'term', 'enrollment.student'])
            ->when($request->term_id, fn($q, $t) => $q->where('term_id', $t))
            ->when($request->subject_id, fn($q, $s) => $q->where('subject_id', $s))
            ->when($request->academic_year_id, fn($q, $y) => $q->where('academic_year_id', $y))
            ->when($request->enrollment_id, fn($q, $e) => $q->where('enrollment_id', $e))
            ->when($request->situation, fn($q, $s) => $q->where('situation', $s));

        // Students: only own results
        if ($user->hasRole('student') && $user->student) {
            $enrollment = \App\Models\Enrollment::where('student_id', $user->student->id)->first();
            if ($enrollment) {
                $query->where('enrollment_id', $enrollment->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Guardians: only their wards
        if ($user->hasRole('guardian') && $user->guardian) {
            $studentIds = $user->guardian->students()->pluck('students.id');
            $enrollmentIds = \App\Models\Enrollment::whereIn('student_id', $studentIds)->pluck('id');
            $query->whereIn('enrollment_id', $enrollmentIds);
        }

        // Class filter via enrollment
        if ($request->class_id) {
            $enrollmentIds = \App\Models\Enrollment::where('class_id', $request->class_id)->pluck('id');
            $query->whereIn('enrollment_id', $enrollmentIds);
        }

        $results = $query->get();

        return response()->json([
            'message' => 'Resultados trimestrais obtidos com sucesso.',
            'data'    => TermResultResource::collection($results),
        ]);
    }

    private function annualResults(Request $request, $user): JsonResponse
    {
        $query = AnnualResult::with(['subject', 'enrollment.student'])
            ->when($request->subject_id, fn($q, $s) => $q->where('subject_id', $s))
            ->when($request->academic_year_id, fn($q, $y) => $q->where('academic_year_id', $y))
            ->when($request->enrollment_id, fn($q, $e) => $q->where('enrollment_id', $e))
            ->when($request->situation, fn($q, $s) => $q->where('situation', $s));

        // Students: only own results
        if ($user->hasRole('student') && $user->student) {
            $enrollment = \App\Models\Enrollment::where('student_id', $user->student->id)->first();
            if ($enrollment) {
                $query->where('enrollment_id', $enrollment->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Class filter
        if ($request->class_id) {
            $enrollmentIds = \App\Models\Enrollment::where('class_id', $request->class_id)->pluck('id');
            $query->whereIn('enrollment_id', $enrollmentIds);
        }

        $results = $query->get();

        return response()->json([
            'message' => 'Resultados anuais obtidos com sucesso.',
            'data'    => AnnualResultResource::collection($results),
        ]);
    }
}
