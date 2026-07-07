<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    public function __construct(
        protected TenantDatabaseManager $dbManager
    ) {}

    /**
     * Authenticate user credentials on the resolved tenant.
     *
     * @param Client $client
     * @param string $email
     * @param string $password
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function authenticate(Client $client, string $email, string $password): array
    {
        // 1. Switch the active database connection to the resolved client's database
        $this->dbManager->connect($client);

        // 2. Fetch the user in the tenant database
        $user = User::where('email', $email)->first();

        // 3. Verify password
        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        // 4. Generate Sanctum token on the tenant's database
        $tokenInstance = $user->createToken('auth_token');
        $plainTextToken = $tokenInstance->plainTextToken;

        // 5. Prefix the plainTextToken (which is "id|token") with the client code
        // Resulting token sent to client will be "client_code|id|token"
        $prefixedToken = "{$client->client_code}|{$plainTextToken}";

        return [
            'user' => $user,
            'token' => $prefixedToken,
        ];
    }
}
