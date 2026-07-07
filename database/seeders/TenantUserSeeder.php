<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use App\Services\TenantDatabaseManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantUserSeeder extends Seeder
{
    /**
     * Injecting TenantDatabaseManager to handle database switching during seeding.
     */
    public function __construct(
        protected TenantDatabaseManager $dbManager
    ) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();

        foreach ($clients as $client) {
            // Switch connection to the current client's database
            $this->dbManager->connect($client);

            // Dynamically run the tenant migrations on the active tenant connection
            \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            $email = match ($client->client_code) {
                'ibm' => 'ibmuser@gmail.com',
                'hcl' => 'hcluser@gmail.com',
                'infosys' => 'infyuser@gmail.com',
                default => "user@{$client->client_code}.com",
            };

            // Create or update the tenant user
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => "{$client->client_name} User",
                    'password' => Hash::make('password'),
                ]
            );
        }

        // Revert default connection back to the landing database
        $this->dbManager->disconnect();
    }
}
