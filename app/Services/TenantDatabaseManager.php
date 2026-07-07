<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantDatabaseManager
{
    /**
     * Connect to a specific tenant database.
     *
     * @param Client $client
     * @return void
     */
    public function connect(Client $client): void
    {
        // 1. Dynamically configure Laravel database connection at runtime
        Config::set('database.connections.tenant.host', $client->db_server);
        Config::set('database.connections.tenant.port', $client->db_port);
        Config::set('database.connections.tenant.database', $client->db_name);
        Config::set('database.connections.tenant.username', $client->db_user);
        Config::set('database.connections.tenant.password', $client->db_password);

        // 2. Purge previous connection
        DB::purge('tenant');

        // 3. Reconnect to tenant database
        DB::reconnect('tenant');

        // 4. Set tenant as default connection so that all default models query the tenant DB
        DB::setDefaultConnection('tenant');
    }

    /**
     * Reset the connection back to the landing/master database.
     *
     * @return void
     */
    public function disconnect(): void
    {
        DB::purge('tenant');
        DB::setDefaultConnection('landing');
    }
}
