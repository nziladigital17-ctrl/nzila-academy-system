<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTuitionPlanRequest;
use App\Http\Requests\Finance\UpdateTuitionPlanRequest;
use App\Http\Resources\Finance\TuitionPlanResource;
use App\Models\TuitionPlan;
use Illuminate\Http\Request;

class TuitionPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = TuitionPlan::with('academicYear');

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return TuitionPlanResource::collection($query->paginate(15));
    }

    public function store(StoreTuitionPlanRequest $request)
    {
        $data = $request->validated();
        if (empty($data['school_id'])) {
            $data['school_id'] = auth()->user()->school_id;
        }

        $tuitionPlan = TuitionPlan::create($data);

        return response()->json([
            'message' => 'Plano de propina criado com sucesso.',
            'data' => new TuitionPlanResource($tuitionPlan),
        ], 201);
    }

    public function show(TuitionPlan $tuitionPlan)
    {
        $tuitionPlan->load(['academicYear', 'schoolClass', 'student']);
        $tuitionPlan->loadCount('assignments');

        return new TuitionPlanResource($tuitionPlan);
    }

    public function update(UpdateTuitionPlanRequest $request, TuitionPlan $tuitionPlan)
    {
        $tuitionPlan->update($request->validated());

        return response()->json([
            'message' => 'Plano de propina atualizado com sucesso.',
            'data' => new TuitionPlanResource($tuitionPlan),
        ]);
    }

    public function destroy(TuitionPlan $tuitionPlan)
    {
        if ($tuitionPlan->assignments()->count() > 0) {
            return response()->json([
                'message' => 'Não é possível excluir um plano de propina com atribuições.'
            ], 422);
        }

        $tuitionPlan->delete();

        return response()->json([
            'message' => 'Plano de propina excluído com sucesso.'
        ]);
    }
}
