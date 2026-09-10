<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTermRequest;
use App\Http\Requests\UpdateTermRequest;
use App\Http\Resources\TermResource;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function index(Request $request, AcademicYear $academicYear)
    {
        if ($request->user()->school_id && $academicYear->school_id !== $request->user()->school_id) {
            abort(403);
        }

        return TermResource::collection($academicYear->terms);
    }

    public function store(StoreTermRequest $request)
    {
        $term = Term::create($request->validated());

        return new TermResource($term);
    }

    public function show(Term $term)
    {
        if (request()->user()->school_id && $term->academicYear->school_id !== request()->user()->school_id) {
            abort(403);
        }
        
        return new TermResource($term);
    }

    public function update(UpdateTermRequest $request, Term $term)
    {
        if ($request->user()->school_id && $term->academicYear->school_id !== $request->user()->school_id) {
            abort(403);
        }

        $term->update($request->validated());

        return new TermResource($term);
    }

    public function destroy(Term $term)
    {
        if (request()->user()->school_id && $term->academicYear->school_id !== request()->user()->school_id) {
            abort(403);
        }

        if ($term->assessments()->exists()) {
            return response()->json([
                'message' => 'Não é possível eliminar um trimestre com avaliações.'
            ], 422);
        }

        $term->delete();

        return response()->json(['message' => 'Trimestre eliminado com sucesso.']);
    }
}
