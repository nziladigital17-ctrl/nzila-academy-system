<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAssessmentSettingRequest;
use App\Http\Resources\AssessmentSettingResource;
use App\Models\AssessmentSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssessmentSettingController extends Controller
{
    /**
     * List all assessment settings for the authenticated user's school.
     */
    public function index(Request $request): JsonResponse
    {
        $settings = AssessmentSetting::with('academicYear')
            ->when($request->academic_year_id, fn($q, $y) => $q->where('academic_year_id', $y))
            ->orderBy('academic_year_id', 'desc')
            ->get();

        return response()->json([
            'message' => 'Configurações de avaliação listadas com sucesso.',
            'data'    => AssessmentSettingResource::collection($settings),
        ]);
    }

    /**
     * Show a single assessment setting.
     */
    public function show(AssessmentSetting $assessmentSetting): JsonResponse
    {
        $this->authorizeSchool($assessmentSetting->school_id);

        return response()->json([
            'message' => 'Configuração de avaliação obtida com sucesso.',
            'data'    => new AssessmentSettingResource($assessmentSetting),
        ]);
    }

    /**
     * Update assessment settings (upsert for school/year).
     */
    public function update(UpdateAssessmentSettingRequest $request, AssessmentSetting $assessmentSetting): JsonResponse
    {
        $this->authorizeSchool($assessmentSetting->school_id);

        $assessmentSetting->update($request->validated());

        return response()->json([
            'message' => 'Configuração de avaliação actualizada com sucesso.',
            'data'    => new AssessmentSettingResource($assessmentSetting->fresh()),
        ]);
    }

    /**
     * Ensure the resource belongs to the authenticated user's school.
     */
    private function authorizeSchool(int $schoolId): void
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $user->school_id !== $schoolId) {
            abort(403, 'Acesso não autorizado.');
        }
    }
}
