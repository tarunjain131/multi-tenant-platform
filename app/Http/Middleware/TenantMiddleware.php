<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TenantDatabaseManager;
use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function __construct(
        protected TenantResolver $resolver,
        protected TenantDatabaseManager $dbManager
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated. Token missing.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Token format expected: client_code|id|token_hash
        $parts = explode('|', $bearerToken);

        if (count($parts) < 3) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated. Invalid token format.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $clientCode = $parts[0];
        // Reconstruct the original Sanctum token (id|token_hash)
        $originalToken = implode('|', array_slice($parts, 1));

        // Resolve the client from the master database
        $client = $this->resolver->resolveByClientCode($clientCode);

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated. Client tenant not found.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Dynamically configure and connect to the resolved tenant database
        $this->dbManager->connect($client);

        // Rewrite the Authorization header to the original Sanctum token
        // so that Sanctum's default guard can validate it against the tenant database.
        $request->headers->set('Authorization', 'Bearer ' . $originalToken);

        return $next($request);
    }
}
