<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $authUser = $request->user();

        // Non-super-admins can only see their own school
        if ($authUser->school_id) {
            $school = School::find($authUser->school_id);
            return response()->json([
                'data' => [$school],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 1,
            ]);
        }

        // Super admin sees all schools
        $schools = School::paginate(15);
        return response()->json($schools);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:schools,code',
            'nif' => 'nullable|string|max:50|unique:schools,nif',
            'address' => 'nullable|string',
            'province' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $school = School::create($validated);

        return response()->json([
            'message' => 'Escola criada com sucesso.',
            'data' => $school,
        ], 201);
    }

    public function show(Request $request, School $school): JsonResponse
    {
        $this->authorizeSchoolAccess($request->user(), $school);

        return response()->json(['data' => $school]);
    }

    public function update(Request $request, School $school): JsonResponse
    {
        $this->authorizeSchoolAccess($request->user(), $school);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:schools,code,' . $school->id,
            'nif' => 'nullable|string|max:50|unique:schools,nif,' . $school->id,
            'address' => 'nullable|string',
            'province' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $school->update($validated);

        return response()->json([
            'message' => 'Escola actualizada com sucesso.',
            'data' => $school,
        ]);
    }

    public function destroy(Request $request, School $school): JsonResponse
    {
        $this->authorizeSchoolAccess($request->user(), $school);

        $school->delete();

        return response()->json([
            'message' => 'Escola desactivada com sucesso.',
        ]);
    }

    /**
     * Non-super-admins can only access their own school.
     */
    private function authorizeSchoolAccess($authUser, School $school): void
    {
        if ($authUser->school_id && $authUser->school_id !== $school->id) {
            abort(403, 'Não tem permissão para aceder a dados de outra escola.');
        }
    }
}
