<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreExpenseRequest;
use App\Http\Requests\Finance\VoidExpenseRequest;
use App\Http\Resources\Finance\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    protected ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    public function index(Request $request)
    {
        $query = Expense::with(['expenseCategory']);

        if ($request->filled('expense_category_id')) {
            $query->where('expense_category_id', $request->expense_category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('expense_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('expense_date', '<=', $request->to_date);
        }
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('reference_number', 'like', '%' . $request->search . '%');
        }

        return ExpenseResource::collection($query->paginate(15));
    }

    public function store(StoreExpenseRequest $request)
    {
        $data = $request->validated();
        if (empty($data['school_id'])) {
            $data['school_id'] = auth()->user()->school_id;
        }

        $expense = $this->expenseService->createExpense($data, $data['school_id']);

        return response()->json([
            'message' => 'Despesa criada com sucesso.',
            'data' => new ExpenseResource($expense),
        ], 201);
    }

    public function show(Expense $expense)
    {
        $expense->load(['category', 'approvedByUser']);

        return new ExpenseResource($expense);
    }

    public function update(Request $request, Expense $expense)
    {
        if ($expense->status !== 'draft') {
            return response()->json([
                'message' => 'Apenas despesas em rascunho podem ser editadas.'
            ], 422);
        }

        $validated = $request->validate([
            'expense_category_id' => 'sometimes|exists:expense_categories,id',
            'amount' => 'sometimes|numeric|min:0',
            'expense_date' => 'sometimes|date',
            'description' => 'sometimes|string',
            'reference_number' => 'nullable|string',
        ]);

        $expense->update($validated);

        return response()->json([
            'message' => 'Despesa atualizada com sucesso.',
            'data' => new ExpenseResource($expense),
        ]);
    }

    public function confirm(Expense $expense)
    {
        $this->expenseService->confirmExpense($expense);

        return response()->json([
            'message' => 'Despesa confirmada com sucesso.',
            'data' => new ExpenseResource($expense->fresh()),
        ]);
    }

    public function void(VoidExpenseRequest $request, Expense $expense)
    {
        try {
            $this->expenseService->voidExpense($expense, $request->reason);
            return response()->json([
                'message' => 'Despesa anulada com sucesso.',
                'data' => new ExpenseResource($expense->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}




