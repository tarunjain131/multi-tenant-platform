<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthenticationService;
use App\Services\TenantResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class LoginController extends Controller
{
    public function __construct(
        protected TenantResolver $tenantResolver,
        protected AuthenticationService $authService
    ) {}

    /**
     * Authenticate user and generate Sanctum token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');

        // 1. Search every tenant to identify the client matching this user email
        $client = $this->tenantResolver->resolveByEmail($email);

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        try {
            // 2. Read tenant database credentials from master, connect, and authenticate
            $result = $this->authService->authenticate($client, $email, $password);

            return response()->json([
                'status' => true,
                'client' => $client->client_name,
                'token' => $result['token'],
                'user' => new UserResource($result['user']),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }
    }

    /**
     * Terminate the session and revoke token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Fetch authenticated user details.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'user' => new UserResource($request->user()),
        ]);
    }
}
