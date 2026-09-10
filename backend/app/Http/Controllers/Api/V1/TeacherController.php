<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = Teacher::query();

        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return TeacherResource::collection($query->paginate(15));
    }

    public function store(StoreTeacherRequest $request)
    {
        $validated = $request->validated();

        if (!$request->user()->school_id) {
            $request->validate(['school_id' => 'required|exists:schools,id']);
            $validated['school_id'] = $request->input('school_id');
        } else {
            $validated['school_id'] = $request->user()->school_id;
        }

        $teacher = Teacher::create($validated);

        return (new TeacherResource($teacher))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Teacher $teacher)
    {
        return new TeacherResource($teacher->load('assignments.schoolClass', 'assignments.subject'));
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher)
    {
        $teacher->update($request->validated());

        return new TeacherResource($teacher);
    }

    public function destroy(Teacher $teacher)
    {
        if ($teacher->assignments()->exists()) {
            return response()->json([
                'message' => 'Não é possível eliminar um professor com associações académicas ativas.'
            ], 422);
        }

        $teacher->delete();

        return response()->json(['message' => 'Professor eliminado com sucesso.']);
    }
}
