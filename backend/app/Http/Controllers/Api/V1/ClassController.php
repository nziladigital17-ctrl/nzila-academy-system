<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Http\Resources\ClassResource;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $query = SchoolClass::query();

        if ($request->has('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->has('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->has('shift')) {
            $query->where('shift', $request->shift);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        return ClassResource::collection($query->paginate(15));
    }

    public function store(StoreClassRequest $request)
    {
        $validated = $request->validated();

        if (!$request->user()->school_id) {
            $request->validate(['school_id' => 'required|exists:schools,id']);
            $validated['school_id'] = $request->input('school_id');
        } else {
            $validated['school_id'] = $request->user()->school_id;
        }

        $schoolClass = SchoolClass::create($validated);

        return (new ClassResource($schoolClass))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SchoolClass $class)
    {
        return new ClassResource($class);
    }

    public function update(UpdateClassRequest $request, SchoolClass $class)
    {
        $class->update($request->validated());

        return new ClassResource($class);
    }

    public function destroy(SchoolClass $class)
    {
        if ($class->enrollments()->exists() || $class->teachers()->exists()) {
            return response()->json([
                'message' => 'Não é possível eliminar uma turma com matrículas ou professores associados.'
            ], 422);
        }

        $class->delete();

        return response()->json(['message' => 'Turma eliminada com sucesso.']);
    }
}
