<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicYearRequest;
use App\Http\Requests\UpdateAcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index(Request $request)
    {
        $query = AcademicYear::query();

        if ($request->has('current')) {
            $query->where('is_current', $request->current === 'true');
        }

        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        return AcademicYearResource::collection($query->paginate(15));
    }

    public function store(StoreAcademicYearRequest $request)
    {
        $validated = $request->validated();

        if (!$request->user()->school_id) {
            $request->validate(['school_id' => 'required|exists:schools,id']);
            $validated['school_id'] = $request->input('school_id');
        } else {
            $validated['school_id'] = $request->user()->school_id;
        }

        if (isset($validated['is_current']) && $validated['is_current']) {
            AcademicYear::where('school_id', $validated['school_id'])->update(['is_current' => false]);
        }

        $academicYear = AcademicYear::create($validated);

        return (new AcademicYearResource($academicYear))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AcademicYear $academicYear)
    {
        return new AcademicYearResource($academicYear->load('terms'));
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear)
    {
        $validated = $request->validated();

        if (isset($validated['is_current']) && $validated['is_current']) {
            AcademicYear::where('school_id', $academicYear->school_id)
                ->where('id', '!=', $academicYear->id)
                ->update(['is_current' => false]);
        }

        $academicYear->update($validated);

        return new AcademicYearResource($academicYear);
    }

    public function destroy(AcademicYear $academicYear)
    {
        if ($academicYear->terms()->exists() || $academicYear->classes()->exists()) {
            return response()->json([
                'message' => 'Não é possível eliminar um ano letivo com trimestres ou turmas.'
            ], 422);
        }

        $academicYear->delete();

        return response()->json(['message' => 'Ano letivo eliminado com sucesso.']);
    }
}
