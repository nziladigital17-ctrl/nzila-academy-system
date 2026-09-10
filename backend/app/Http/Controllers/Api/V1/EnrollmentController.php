<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(private EnrollmentService $enrollmentService)
    {
    }

    public function index(Request $request)
    {
        $query = Enrollment::with('student', 'schoolClass');

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('academic_year_id')) {
            $query->whereHas('schoolClass', function ($q) use ($request) {
                $q->where('academic_year_id', $request->academic_year_id);
            });
        }

        return EnrollmentResource::collection($query->paginate(15));
    }

    public function store(StoreEnrollmentRequest $request)
    {
        $schoolId = $request->user()->school_id;

        // For super admin, derive school_id from the student
        if (!$schoolId) {
            $student = \App\Models\Student::withoutGlobalScopes()->findOrFail($request->input('student_id'));
            $schoolId = $student->school_id;
        }

        try {
            $enrollment = $this->enrollmentService->enroll(
                $request->input('student_id'),
                $request->input('class_id'),
                $schoolId
            );

            return (new EnrollmentResource($enrollment->load('student', 'schoolClass')))
                ->response()
                ->setStatusCode(201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Enrollment $enrollment)
    {
        return new EnrollmentResource($enrollment->load('student', 'schoolClass'));
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,cancelled,completed',
        ]);

        $enrollment->update($validated);

        return new EnrollmentResource($enrollment);
    }

    public function destroy(Enrollment $enrollment)
    {
        if ($enrollment->grades()->exists()) {
            return response()->json([
                'message' => 'Não é possível eliminar uma matrícula com notas registadas.'
            ], 422);
        }

        $enrollment->delete();

        return response()->json(['message' => 'Matrícula eliminada com sucesso.']);
    }
}
