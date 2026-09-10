<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherAssignmentRequest;
use App\Http\Requests\UpdateTeacherAssignmentRequest;
use App\Http\Resources\TeacherAssignmentResource;
use App\Models\TeacherAssignment;
use Illuminate\Http\Request;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = TeacherAssignment::with('teacher', 'schoolClass', 'subject', 'academicYear');

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        return TeacherAssignmentResource::collection($query->paginate(15));
    }

    public function store(StoreTeacherAssignmentRequest $request)
    {
        $validated = $request->validated();

        // Set school_id from the class
        $class = \App\Models\SchoolClass::withoutGlobalScopes()->findOrFail($validated['class_id']);
        $validated['school_id'] = $class->school_id;

        $assignment = TeacherAssignment::create($validated);

        return (new TeacherAssignmentResource($assignment->load('teacher', 'schoolClass', 'subject', 'academicYear')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(TeacherAssignment $teacherAssignment)
    {
        return new TeacherAssignmentResource(
            $teacherAssignment->load('teacher', 'schoolClass', 'subject', 'academicYear')
        );
    }

    public function update(UpdateTeacherAssignmentRequest $request, TeacherAssignment $teacherAssignment)
    {
        $teacherAssignment->update($request->validated());

        return new TeacherAssignmentResource($teacherAssignment);
    }

    public function destroy(TeacherAssignment $teacherAssignment)
    {
        $teacherAssignment->delete();

        return response()->json(['message' => 'Associação eliminada com sucesso.']);
    }
}
