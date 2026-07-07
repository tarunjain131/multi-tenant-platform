<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantAuthTest extends TestCase
{
    /**
     * Test successful login across different tenants.
     */
    public function test_successful_login_for_tenants(): void
    {
        $tenants = [
            'ibm' => [
                'email' => 'ibmuser@gmail.com',
                'name' => 'IBM User',
                'client_name' => 'IBM',
            ],
            'hcl' => [
                'email' => 'hcluser@gmail.com',
                'name' => 'HCL User',
                'client_name' => 'HCL',
            ],
            'infosys' => [
                'email' => 'infyuser@gmail.com',
                'name' => 'Infosys User',
                'client_name' => 'Infosys',
            ],
        ];

        foreach ($tenants as $code => $data) {
            $response = $this->postJson('/api/login', [
                'email' => $data['email'],
                'password' => 'password',
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'client',
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                    ],
                ])
                ->assertJson([
                    'status' => true,
                    'client' => $data['client_name'],
                    'user' => [
                        'name' => $data['name'],
                        'email' => $data['email'],
                    ],
                ]);

            $this->assertStringStartsWith("{$code}|", $response->json('token'));
        }
    }

    /**
     * Test login failure with invalid credentials.
     */
    public function test_login_failure_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'ibmuser@gmail.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test fetching authenticated profile (me) and logging out.
     */
    public function test_authenticated_profile_and_logout(): void
    {
        // 1. Log in to get token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'ibmuser@gmail.com',
            'password' => 'password',
        ]);

        $token = $loginResponse->json('token');

        // 2. Fetch profile using token
        $profileResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $profileResponse->assertStatus(200)
            ->assertJson([
                'status' => true,
                'user' => [
                    'email' => 'ibmuser@gmail.com',
                    'name' => 'IBM User',
                ],
            ]);

        // 3. Logout
        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Logged out successfully',
            ]);

        // Clear auth guard cache to force Sanctum to re-evaluate the token on the next request
        \Illuminate\Support\Facades\Auth::forgetGuards();

        // 4. Try fetching profile again (should be unauthenticated)
        $recheckResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $recheckResponse->assertStatus(401);
    }

    /**
     * Test accessing authenticated route with invalid token format.
     */
    public function test_authenticated_route_with_invalid_token_format(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalidtokenformat')
            ->getJson('/api/me');

        $response->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Unauthenticated. Invalid token format.',
            ]);
    }

    /**
     * Test accessing authenticated route with non-existent tenant prefix.
     */
    public function test_authenticated_route_with_non_existent_tenant(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer nonexistent|1|tokenhash')
            ->getJson('/api/me');

        $response->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Unauthenticated. Client tenant not found.',
            ]);
    }
}
