<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Http\Resources\AssessmentResource;
use App\Models\Assessment;
use App\Models\TeacherAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssessmentController extends Controller
{
    /**
     * List assessments. Teachers see only their own assignments.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = Auth::user();
        $query = Assessment::with(['subject', 'term'])
            ->when($request->term_id, fn($q, $t) => $q->where('term_id', $t))
            ->when($request->class_id, fn($q, $c) => $q->where('class_id', $c))
            ->when($request->subject_id, fn($q, $s) => $q->where('subject_id', $s))
            ->when($request->type, fn($q, $type) => $q->where('type', $type));

        // Teachers: restrict to their own assignments
        if ($user->hasRole('teacher')) {
            $teacher = $user->teacher;
            if ($teacher) {
                $assignmentIds = TeacherAssignment::where('teacher_id', $teacher->id)->pluck('id');
                $query->whereIn('teacher_assignment_id', $assignmentIds);
            } else {
                $query->whereRaw('1 = 0'); // No results if not a teacher profile
            }
        }

        $assessments = $query->orderBy('date', 'desc')->paginate(50);

        return response()->json([
            'message' => 'Avaliações listadas com sucesso.',
            'data'    => AssessmentResource::collection($assessments->items()),
            'meta'    => [
                'current_page' => $assessments->currentPage(),
                'last_page'    => $assessments->lastPage(),
                'total'        => $assessments->total(),
            ],
        ]);
    }

    /**
     * Show a single assessment.
     */
    public function show(Assessment $assessment): JsonResponse
    {
        $this->authorizeAccess($assessment);

        $assessment->load(['subject', 'term', 'activeGrades']);

        return response()->json([
            'message' => 'Avaliação obtida com sucesso.',
            'data'    => new AssessmentResource($assessment),
        ]);
    }

    /**
     * Create a new assessment.
     */
    public function store(StoreAssessmentRequest $request): JsonResponse
    {
        $assessment = Assessment::create($request->validated());

        return response()->json([
            'message' => 'Avaliação criada com sucesso.',
            'data'    => new AssessmentResource($assessment->load('subject', 'term')),
        ], 201);
    }

    /**
     * Update an assessment (only label, date, is_active).
     */
    public function update(UpdateAssessmentRequest $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeAccess($assessment);
        $assessment->update($request->validated());

        return response()->json([
            'message' => 'Avaliação actualizada com sucesso.',
            'data'    => new AssessmentResource($assessment->fresh()->load('subject', 'term')),
        ]);
    }

    /**
     * Delete an assessment (only if no grades exist).
     */
    public function destroy(Assessment $assessment): JsonResponse
    {
        $this->authorizeAccess($assessment);

        if ($assessment->activeGrades()->exists()) {
            return response()->json([
                'message' => 'Não é possível eliminar uma avaliação com notas lançadas.',
            ], 422);
        }

        $assessment->delete();

        return response()->json(['message' => 'Avaliação eliminada com sucesso.']);
    }

    /**
     * Ensure the teacher can only access their own assessment.
     */
    private function authorizeAccess(Assessment $assessment): void
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->hasRole('teacher')) {
            $teacher = $user->teacher;
            if (!$teacher) {
                abort(403);
            }
            $myAssignmentIds = TeacherAssignment::where('teacher_id', $teacher->id)->pluck('id');
            if (!in_array($assessment->teacher_assignment_id, $myAssignmentIds->toArray())) {
                abort(403, 'Não tem acesso a esta avaliação.');
            }
        }
    }
}
