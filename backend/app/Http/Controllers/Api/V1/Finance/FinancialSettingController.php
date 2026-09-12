<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\UpsertFinancialSettingRequest;
use App\Http\Resources\Finance\FinancialSettingResource;
use App\Models\FinancialSetting;
use Illuminate\Http\Request;

class FinancialSettingController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $schoolId = auth()->user()->school_id;

        $settings = FinancialSetting::where('school_id', $schoolId)
            ->where('academic_year_id', $request->academic_year_id)
            ->get();

        return FinancialSettingResource::collection($settings);
    }

    public function upsert(UpsertFinancialSettingRequest $request)
    {
        $schoolId = auth()->user()->school_id;
        $academicYearId = $request->academic_year_id;
        $settingsData = $request->settings;

        foreach ($settingsData as $setting) {
            FinancialSetting::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYearId,
                    'setting_key' => $setting['setting_key'],
                ],
                [
                    'setting_value' => $setting['setting_value'],
                    'setting_type' => $setting['setting_type'] ?? 'string',
                    'description' => $setting['description'] ?? null,
                ]
            );
        }

        $settings = FinancialSetting::where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->get();

        return response()->json([
            'message' => 'Configurações financeiras salvas com sucesso.',
            'data' => FinancialSettingResource::collection($settings),
        ]);
    }
}
