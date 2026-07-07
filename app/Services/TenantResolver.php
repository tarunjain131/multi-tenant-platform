<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Throwable;

class TenantResolver
{
    public function __construct(
        protected TenantDatabaseManager $dbManager
    ) {}

    /**
     * Resolve tenant by client code.
     *
     * @param string $code
     * @return Client|null
     */
    public function resolveByClientCode(string $code): ?Client
    {
        return Client::where('client_code', strtolower($code))->first();
    }

    /**
     * Resolve tenant by user email by searching all client databases.
     *
     * @param string $email
     * @return Client|null
     */
    public function resolveByEmail(string $email): ?Client
    {
        // Get all clients from master database
        $clients = Client::all();

        foreach ($clients as $client) {
            try {
                // Dynamically connect to the candidate tenant database
                $this->dbManager->connect($client);

                // Query the users table in this tenant database
                $userExists = DB::connection('tenant')
                    ->table('users')
                    ->where('email', $email)
                    ->exists();

                if ($userExists) {
                    return $client;
                }
            } catch (Throwable $e) {
                // If connection fails, continue to the next client database
                continue;
            }
        }

        // If no tenant user was found, disconnect from tenant database
        $this->dbManager->disconnect();

        return null;
    }
}
