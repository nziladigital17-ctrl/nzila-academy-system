<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuardianRequest;
use App\Http\Requests\UpdateGuardianRequest;
use App\Http\Resources\GuardianResource;
use App\Models\Guardian;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    public function index(Request $request)
    {
        $query = Guardian::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('relationship')) {
            $query->where('relationship', $request->relationship);
        }

        return GuardianResource::collection($query->paginate(15));
    }

    public function store(StoreGuardianRequest $request)
    {
        $validated = $request->validated();

        if (!$request->user()->school_id) {
            $request->validate(['school_id' => 'required|exists:schools,id']);
            $validated['school_id'] = $request->input('school_id');
        } else {
            $validated['school_id'] = $request->user()->school_id;
        }

        $guardian = Guardian::create($validated);

        return (new GuardianResource($guardian))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Guardian $guardian)
    {
        return new GuardianResource($guardian->load('students'));
    }

    public function update(UpdateGuardianRequest $request, Guardian $guardian)
    {
        $guardian->update($request->validated());

        return new GuardianResource($guardian);
    }

    public function destroy(Guardian $guardian)
    {
        if ($guardian->students()->exists()) {
            return response()->json([
                'message' => 'Não é possível eliminar um encarregado de educação com alunos associados.'
            ], 422);
        }

        $guardian->delete();

        return response()->json(['message' => 'Encarregado de educação eliminado com sucesso.']);
    }
}
