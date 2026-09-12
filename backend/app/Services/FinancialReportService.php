<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialReportService
{
    public function financialSummary(int $schoolId, ?int $academicYearId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $paymentsQuery = Payment::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('status', 'confirmed');
            
        $expensesQuery = Expense::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('status', 'confirmed');

        if ($startDate) {
            $paymentsQuery->where('paid_at', '>=', $startDate);
            $expensesQuery->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $paymentsQuery->where('paid_at', '<=', $endDate);
            $expensesQuery->where('date', '<=', $endDate);
        }

        $totalRevenue = (float) $paymentsQuery->sum('amount');
        $totalExpenses = (float) $expensesQuery->sum('amount');
        $net = $totalRevenue - $totalExpenses;
        $paymentsCount = $paymentsQuery->count();
        $expensesCount = $expensesQuery->count();

        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_income' => round($net, 2),
            'payments_count' => $paymentsCount,
            'expenses_count' => $expensesCount,
        ];
    }

    public function revenueByPeriod(int $schoolId, string $startDate, string $endDate, string $groupBy = 'month'): array
    {
        $payments = Payment::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('status', 'confirmed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->get(['amount', 'paid_at']);

        $grouped = $payments->groupBy(function($payment) use ($groupBy) {
            $date = Carbon::parse($payment->paid_at);
            if ($groupBy === 'year') return $date->format('Y');
            if ($groupBy === 'quarter') return $date->format('Y') . '-Q' . $date->quarter;
            if ($groupBy === 'day') return $date->format('Y-m-d');
            return $date->format('Y-m'); // month
        });

        $result = [];
        foreach ($grouped as $period => $items) {
            $result[] = [
                'period' => $period,
                'amount' => round($items->sum('amount'), 2),
            ];
        }

        usort($result, fn($a, $b) => strcmp($a['period'], $b['period']));

        return $result;
    }

    public function expensesByPeriod(int $schoolId, string $startDate, string $endDate, string $groupBy = 'month'): array
    {
        $expenses = Expense::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('status', 'confirmed')
            ->whereBetween('date', [$startDate, $endDate])
            ->get(['amount', 'date']);

        $grouped = $expenses->groupBy(function($expense) use ($groupBy) {
            $date = Carbon::parse($expense->date);
            if ($groupBy === 'year') return $date->format('Y');
            if ($groupBy === 'quarter') return $date->format('Y') . '-Q' . $date->quarter;
            if ($groupBy === 'day') return $date->format('Y-m-d');
            return $date->format('Y-m'); // month
        });

        $result = [];
        foreach ($grouped as $period => $items) {
            $result[] = [
                'period' => $period,
                'amount' => round($items->sum('amount'), 2),
            ];
        }

        usort($result, fn($a, $b) => strcmp($a['period'], $b['period']));

        return $result;
    }

    public function outstandingBalances(int $schoolId): array
    {
        $invoices = Invoice::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->with(['payments' => fn($q) => $q->where('status', 'confirmed')])
            ->get();

        $summary = [
            'total_owed' => 0.0,
            'total_paid' => 0.0,
            'total_balance' => 0.0,
            'by_status' => [
                'pending' => 0.0,
                'partial' => 0.0,
                'overdue' => 0.0,
            ]
        ];

        foreach ($invoices as $invoice) {
            $owed = (float) $invoice->total;
            $paid = (float) $invoice->payments->sum('amount');
            $balance = max(0, $owed - $paid);

            $summary['total_owed'] += $owed;
            $summary['total_paid'] += $paid;
            $summary['total_balance'] += $balance;
            
            if (isset($summary['by_status'][$invoice->status])) {
                $summary['by_status'][$invoice->status] += $balance;
            }
        }

        $summary['total_owed'] = round($summary['total_owed'], 2);
        $summary['total_paid'] = round($summary['total_paid'], 2);
        $summary['total_balance'] = round($summary['total_balance'], 2);
        $summary['by_status']['pending'] = round($summary['by_status']['pending'], 2);
        $summary['by_status']['partial'] = round($summary['by_status']['partial'], 2);
        $summary['by_status']['overdue'] = round($summary['by_status']['overdue'], 2);

        return $summary;
    }
}
