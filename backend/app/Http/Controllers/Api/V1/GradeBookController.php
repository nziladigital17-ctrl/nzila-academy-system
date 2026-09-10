<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GradeBookStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGradeBookRequest;
use App\Http\Requests\UnlockGradeBookRequest;
use App\Http\Resources\GradeBookResource;
use App\Models\GradeBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GradeBookController extends Controller
{
    /**
     * List grade books with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = Auth::user();
        $query = GradeBook::with(['subject', 'term', 'schoolClass'])
            ->when($request->term_id, fn($q, $t) => $q->where('term_id', $t))
            ->when($request->class_id, fn($q, $c) => $q->where('class_id', $c))
            ->when($request->subject_id, fn($q, $s) => $q->where('subject_id', $s))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->academic_year_id, fn($q, $y) => $q->where('academic_year_id', $y));

        // Teachers see only their grade books
        if ($user->hasRole('teacher')) {
            $teacher = $user->teacher;
            $myAssignmentIds = $teacher
                ? \App\Models\TeacherAssignment::where('teacher_id', $teacher->id)->pluck('id')
                : collect();
            $query->whereIn('teacher_assignment_id', $myAssignmentIds);
        }

        $gradeBooks = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json([
            'message' => 'Pautas listadas com sucesso.',
            'data'    => GradeBookResource::collection($gradeBooks->items()),
            'meta'    => [
                'current_page' => $gradeBooks->currentPage(),
                'last_page'    => $gradeBooks->lastPage(),
                'total'        => $gradeBooks->total(),
            ],
        ]);
    }

    /**
     * Show a grade book.
     */
    public function show(GradeBook $gradeBook): JsonResponse
    {
        $gradeBook->load(['subject', 'term', 'schoolClass']);

        return response()->json([
            'message' => 'Pauta obtida com sucesso.',
            'data'    => new GradeBookResource($gradeBook),
        ]);
    }

    /**
     * Create a grade book.
     */
    public function store(StoreGradeBookRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['school_id'] = Auth::user()->school_id;
        $data['status'] = GradeBookStatusEnum::DRAFT->value;

        // Prevent duplicate grade books
        $existing = GradeBook::where([
            'school_id'       => $data['school_id'],
            'academic_year_id'=> $data['academic_year_id'],
            'term_id'         => $data['term_id'],
            'class_id'        => $data['class_id'],
            'subject_id'      => $data['subject_id'],
        ])->first();

        if ($existing) {
            return response()->json([
                'message' => 'Já existe uma pauta para esta turma, disciplina e trimestre.',
                'data'    => new GradeBookResource($existing),
            ], 422);
        }

        $gradeBook = GradeBook::create($data);

        return response()->json([
            'message' => 'Pauta criada com sucesso.',
            'data'    => new GradeBookResource($gradeBook->load('subject', 'term', 'schoolClass')),
        ], 201);
    }

    /**
     * Submit grade book for review (draft → submitted).
     */
    public function submit(Request $request, GradeBook $gradeBook): JsonResponse
    {
        if (!$gradeBook->canTransitionTo(GradeBookStatusEnum::SUBMITTED)) {
            return response()->json([
                'message' => 'A pauta não pode ser submetida no estado actual: ' . $gradeBook->status->displayName(),
            ], 422);
        }

        $gradeBook->update([
            'status'       => GradeBookStatusEnum::SUBMITTED->value,
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Pauta submetida para revisão com sucesso.',
            'data'    => new GradeBookResource($gradeBook->fresh()),
        ]);
    }

    /**
     * Publish grade book (submitted → published). Coordinator/Admin only.
     */
    public function publish(Request $request, GradeBook $gradeBook): JsonResponse
    {
        if (!$gradeBook->canTransitionTo(GradeBookStatusEnum::PUBLISHED)) {
            return response()->json([
                'message' => 'A pauta não pode ser publicada no estado actual: ' . $gradeBook->status->displayName(),
            ], 422);
        }

        DB::transaction(function () use ($gradeBook) {
            $gradeBook->update([
                'status'       => GradeBookStatusEnum::PUBLISHED->value,
                'published_by' => Auth::id(),
                'published_at' => now(),
            ]);

            // On publication, lock the grade book immediately
            $gradeBook->update([
                'status'    => GradeBookStatusEnum::LOCKED->value,
                'locked_by' => Auth::id(),
                'locked_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Pauta publicada e bloqueada com sucesso.',
            'data'    => new GradeBookResource($gradeBook->fresh()),
        ]);
    }

    /**
     * Lock grade book (published → locked). Coordinator/Admin only.
     */
    public function lock(Request $request, GradeBook $gradeBook): JsonResponse
    {
        if (!$gradeBook->canTransitionTo(GradeBookStatusEnum::LOCKED)) {
            return response()->json([
                'message' => 'A pauta não pode ser bloqueada no estado actual: ' . $gradeBook->status->displayName(),
            ], 422);
        }

        $gradeBook->update([
            'status'    => GradeBookStatusEnum::LOCKED->value,
            'locked_by' => Auth::id(),
            'locked_at' => now(),
        ]);

        return response()->json([
            'message' => 'Pauta bloqueada com sucesso.',
            'data'    => new GradeBookResource($gradeBook->fresh()),
        ]);
    }

    /**
     * Unlock grade book (locked → published). Requires mandatory reason.
     */
    public function unlock(UnlockGradeBookRequest $request, GradeBook $gradeBook): JsonResponse
    {
        if (!$gradeBook->canTransitionTo(GradeBookStatusEnum::PUBLISHED)) {
            return response()->json([
                'message' => 'A pauta não pode ser desbloqueada no estado actual: ' . $gradeBook->status->displayName(),
            ], 422);
        }

        $gradeBook->update([
            'status'        => GradeBookStatusEnum::PUBLISHED->value,
            'unlocked_by'   => Auth::id(),
            'unlocked_at'   => now(),
            'unlock_reason' => $request->unlock_reason,
        ]);

        return response()->json([
            'message' => 'Pauta desbloqueada com sucesso. Motivo registado.',
            'data'    => new GradeBookResource($gradeBook->fresh()),
        ]);
    }
}
