<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class DebtorController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? $request->school_id;
        $academicYearId = $request->academic_year_id;

        $debtors = $this->invoiceService->getDebtors($schoolId, $academicYearId);

        return response()->json([
            'data' => $debtors
        ]);
    }
}
