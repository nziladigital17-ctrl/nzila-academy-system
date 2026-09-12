<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    protected FinancialReportService $reportService;

    public function __construct(FinancialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $schoolId = auth()->user()->school_id ?? $request->school_id;

        $summary = $this->reportService->financialSummary(
            $schoolId,
            $validated['academic_year_id'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        return response()->json(['data' => $summary]);
    }

    public function revenueByPeriod(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:month,quarter',
        ]);

        $schoolId = auth()->user()->school_id ?? $request->school_id;

        $revenue = $this->reportService->revenueByPeriod(
            $schoolId,
            $validated['start_date'],
            $validated['end_date'],
            $validated['group_by'] ?? 'month'
        );

        return response()->json(['data' => $revenue]);
    }

    public function expensesByPeriod(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:month,quarter',
        ]);

        $schoolId = auth()->user()->school_id ?? $request->school_id;

        $expenses = $this->reportService->expensesByPeriod(
            $schoolId,
            $validated['start_date'],
            $validated['end_date'],
            $validated['group_by'] ?? 'month'
        );

        return response()->json(['data' => $expenses]);
    }

    public function outstandingBalances(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? $request->school_id;

        $balances = $this->reportService->outstandingBalances($schoolId);

        return response()->json(['data' => $balances]);
    }
}
