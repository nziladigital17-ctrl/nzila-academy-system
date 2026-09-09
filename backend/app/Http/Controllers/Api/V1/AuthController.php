<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login(
            $request->email,
            $request->password
        );

        \Illuminate\Support\Facades\Auth::setUser($result['user']);

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'data' => [
                'user' => $result['user'],
                'token' => $result['token'],
                'permissions' => $result['user']->getAllPermissions(),
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Sessão terminada com sucesso.',
        ]);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles.permissions', 'school');

        return response()->json([
            'data' => [
                'user' => $user,
                'permissions' => $user->getAllPermissions(),
            ],
        ]);
    }

    /**
     * PUT /api/v1/auth/password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $this->authService->updatePassword(
            $request->user(),
            $request->current_password,
            $request->password
        );

        return response()->json([
            'message' => 'Password alterada com sucesso.',
        ]);
    }
}
