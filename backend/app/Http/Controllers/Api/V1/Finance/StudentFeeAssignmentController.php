<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreStudentFeeAssignmentRequest;
use App\Http\Resources\Finance\StudentFeeAssignmentResource;
use App\Models\StudentFeeAssignment;
use Illuminate\Http\Request;

class StudentFeeAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentFeeAssignment::with(['student', 'tuitionPlan']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('tuition_plan_id')) {
            $query->where('tuition_plan_id', $request->tuition_plan_id);
        }
        if ($request->filled('academic_year_id')) {
            $query->whereHas('tuitionPlan', function ($q) use ($request) {
                $q->where('academic_year_id', $request->academic_year_id);
            });
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        return StudentFeeAssignmentResource::collection($query->paginate(15));
    }

    public function store(StoreStudentFeeAssignmentRequest $request)
    {
        $data = $request->validated();
        if (empty($data['school_id'])) {
            $data['school_id'] = auth()->user()->school_id;
        }

        $assignment = StudentFeeAssignment::create($data);

        return response()->json([
            'message' => 'Atribuição de propina criada com sucesso.',
            'data' => new StudentFeeAssignmentResource($assignment),
        ], 201);
    }

    public function show(StudentFeeAssignment $studentFeeAssignment)
    {
        $studentFeeAssignment->load(['student', 'tuitionPlan', 'academicYear']);

        return new StudentFeeAssignmentResource($studentFeeAssignment);
    }

    public function destroy(StudentFeeAssignment $studentFeeAssignment)
    {
        $studentFeeAssignment->delete();

        return response()->json([
            'message' => 'Atribuição de propina excluída com sucesso.'
        ]);
    }
}
