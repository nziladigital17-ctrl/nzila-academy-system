<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreExpenseCategoryRequest;
use App\Http\Resources\Finance\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseCategory::withCount('expenses');

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return ExpenseCategoryResource::collection($query->paginate(15));
    }

    public function store(StoreExpenseCategoryRequest $request)
    {
        $data = $request->validated();
        if (empty($data['school_id'])) {
            $data['school_id'] = auth()->user()->school_id;
        }

        $expenseCategory = ExpenseCategory::create($data);

        return response()->json([
            'message' => 'Categoria de despesa criada com sucesso.',
            'data' => new ExpenseCategoryResource($expenseCategory),
        ], 201);
    }

    public function show(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->loadCount('expenses');

        return new ExpenseCategoryResource($expenseCategory);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $expenseCategory->update($validated);

        return response()->json([
            'message' => 'Categoria de despesa atualizada com sucesso.',
            'data' => new ExpenseCategoryResource($expenseCategory),
        ]);
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($expenseCategory->expenses()->count() > 0) {
            return response()->json([
                'message' => 'Não é possível excluir uma categoria com despesas associadas.'
            ], 422);
        }

        $expenseCategory->delete();

        return response()->json([
            'message' => 'Categoria de despesa excluída com sucesso.'
        ]);
    }
}
