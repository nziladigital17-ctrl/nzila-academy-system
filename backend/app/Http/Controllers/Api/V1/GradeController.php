<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AssessmentTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnnulGradeRequest;
use App\Http\Requests\StoreGradeRequest;
use App\Http\Requests\UpdateGradeRequest;
use App\Http\Resources\GradeResource;
use App\Models\Assessment;
use App\Models\Grade;
use App\Models\GradeBook;
use App\Services\GradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GradeController extends Controller
{
    public function __construct(private readonly GradingService $gradingService) {}

    /**
     * List grades for an assessment.
     */
    public function index(Request $request, Assessment $assessment): JsonResponse
    {
        $grades = $assessment->grades()
            ->with(['enrollment.student', 'gradedByUser'])
            ->when(!$request->boolean('include_annulled'), fn($q) => $q->where('is_annulled', false))
            ->get();

        return response()->json([
            'message' => 'Notas listadas com sucesso.',
            'data'    => GradeResource::collection($grades),
        ]);
    }

    /**
     * Create or update a grade for the assessment.
     * Enforces: PP/PT uniqueness, grade book state, scale 0-20.
     */
    public function store(StoreGradeRequest $request, Assessment $assessment): JsonResponse
    {
        $validated    = $request->validated();
        $enrollmentId = $validated['enrollment_id'];
        $score        = (float) $validated['score'];
        $user         = Auth::user();

        // Validate grade book status (must be draft)
        $gradeBook = GradeBook::where([
            'school_id'  => $assessment->school_id,
            'term_id'    => $assessment->term_id,
            'class_id'   => $assessment->class_id,
            'subject_id' => $assessment->subject_id,
        ])->first();

        if ($gradeBook && !$gradeBook->gradesEditable()) {
            return response()->json([
                'message' => 'Não é possível lançar notas: a pauta não está em estado de rascunho.',
            ], 422);
        }

        return DB::transaction(function () use ($assessment, $enrollmentId, $score, $validated, $gradeBook, $user) {
            // PP and PT: only one active grade allowed per student/subject/term
            if ($assessment->type && $assessment->type->isUnique()) {
                $existing = Grade::whereHas('assessment', function ($q) use ($assessment) {
                    $q->where('subject_id', $assessment->subject_id)
                      ->where('term_id', $assessment->term_id)
                      ->where('type', $assessment->type->value);
                })
                ->where('enrollment_id', $enrollmentId)
                ->where('is_annulled', false)
                ->where('assessment_id', '!=', $assessment->id)
                ->exists();

                if ($existing) {
                    return response()->json([
                        'message' => "Já existe uma nota do tipo {$assessment->type->displayName()} para este aluno neste trimestre. Anule a nota anterior antes de lançar nova.",
                    ], 422);
                }
            }

            $grade = Grade::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'enrollment_id' => $enrollmentId,
                ],
                [
                    'grade_book_id' => $gradeBook?->id,
                    'score'         => $score,
                    'remarks'       => $validated['remarks'] ?? null,
                    'graded_by'     => $user->id,
                    'is_annulled'   => false,
                ]
            );

            // Recalculate MAC/NF/MFA
            $enrollment = $grade->enrollment()->with('schoolClass')->first();
            if ($enrollment) {
                $this->gradingService->recalculate($enrollment, $assessment->subject_id, $assessment->term_id);

                // Generate academic alert if projected NF < passing grade
                $settings = \App\Models\AssessmentSetting::where('school_id', $assessment->school_id)
                    ->where('academic_year_id', $assessment->academic_year_id)
                    ->first();

                if ($settings) {
                    $this->gradingService->checkAndGenerateAcademicAlert(
                        $enrollment,
                        $assessment->subject_id,
                        $assessment->term_id,
                        $assessment->academic_year_id,
                        $settings
                    );
                }
            }

            return response()->json([
                'message' => 'Nota lançada com sucesso.',
                'data'    => new GradeResource($grade->load('assessment', 'gradedByUser')),
            ], 201);
        });
    }

    /**
     * Update a grade (only in draft state).
     */
    public function update(UpdateGradeRequest $request, Assessment $assessment, Grade $grade): JsonResponse
    {
        $gradeBook = $grade->gradeBook;

        if ($gradeBook && !$gradeBook->gradesEditable()) {
            return response()->json([
                'message' => 'A pauta já foi submetida ou publicada. Não é possível editar notas.',
            ], 422);
        }

        DB::transaction(function () use ($request, $grade, $assessment) {
            $grade->update([
                'score'   => $request->score,
                'remarks' => $request->remarks,
            ]);

            $enrollment = $grade->enrollment()->with('schoolClass')->first();
            if ($enrollment) {
                $this->gradingService->recalculate($enrollment, $assessment->subject_id, $assessment->term_id);
            }
        });

        return response()->json([
            'message' => 'Nota actualizada com sucesso.',
            'data'    => new GradeResource($grade->fresh()->load('assessment', 'gradedByUser')),
        ]);
    }

    /**
     * Annul a grade (coordinator/admin only).
     */
    public function annul(AnnulGradeRequest $request, Assessment $assessment, Grade $grade): JsonResponse
    {
        if ($grade->is_annulled) {
            return response()->json(['message' => 'Esta nota já foi anulada.'], 422);
        }

        DB::transaction(function () use ($request, $grade, $assessment) {
            $grade->update([
                'is_annulled'     => true,
                'annulled_reason' => $request->annulled_reason,
                'annulled_by'     => Auth::id(),
                'annulled_at'     => now(),
            ]);

            $enrollment = $grade->enrollment()->with('schoolClass')->first();
            if ($enrollment) {
                $this->gradingService->recalculate($enrollment, $assessment->subject_id, $assessment->term_id);
            }
        });

        return response()->json([
            'message' => 'Nota anulada com sucesso.',
            'data'    => new GradeResource($grade->fresh()),
        ]);
    }
}
