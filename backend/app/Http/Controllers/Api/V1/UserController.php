<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles');

        // Non-super-admins can only see users from their own school
        $authUser = $request->user();
        if ($authUser->school_id) {
            $query->where('school_id', $authUser->school_id);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        $authUser = $request->user();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
        ];

        // Only super admin may specify school_id freely
        if (!$authUser->school_id) {
            $rules['school_id'] = 'nullable|exists:schools,id';
        }

        $validated = $request->validate($rules);
        $validated['password'] = Hash::make($validated['password']);

        // Non-super-admins: force their own school_id
        if ($authUser->school_id) {
            $validated['school_id'] = $authUser->school_id;
        }

        $user = User::create($validated);

        return response()->json([
            'message' => 'Utilizador criado com sucesso.',
            'data' => $user->load('roles'),
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorizeSchoolAccess($request->user(), $user);

        return response()->json([
            'data' => $user->load('roles.permissions', 'school'),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizeSchoolAccess($request->user(), $user);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Utilizador actualizado com sucesso.',
            'data' => $user->load('roles'),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeSchoolAccess($request->user(), $user);

        $user->delete();

        return response()->json([
            'message' => 'Utilizador desactivado com sucesso.',
        ]);
    }

    /**
     * POST /api/v1/users/{user}/roles
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        $this->authorizeSchoolAccess($request->user(), $user);

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->roles()->syncWithoutDetaching([
            $validated['role_id'] => ['school_id' => $user->school_id],
        ]);

        return response()->json([
            'message' => 'Perfil atribuído com sucesso.',
            'data' => $user->load('roles'),
        ]);
    }

    /**
     * DELETE /api/v1/users/{user}/roles/{role}
     */
    public function removeRole(Request $request, User $user, int $roleId): JsonResponse
    {
        $this->authorizeSchoolAccess($request->user(), $user);

        $user->roles()->detach($roleId);

        return response()->json([
            'message' => 'Perfil removido com sucesso.',
            'data' => $user->load('roles'),
        ]);
    }

    /**
     * Verify the authenticated user has access to the target user's school.
     * Super admins bypass this check.
     */
    private function authorizeSchoolAccess(User $authUser, User $targetUser): void
    {
        if ($authUser->school_id && $targetUser->school_id !== $authUser->school_id) {
            abort(403, 'Não tem permissão para aceder a dados de outra escola.');
        }
    }
}
