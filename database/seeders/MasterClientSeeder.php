<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class MasterClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Client::updateOrCreate(
            ['client_code' => 'ibm'],
            [
                'client_name' => 'IBM',
                'db_server' => '127.0.0.1',
                'db_port' => '3306',
                'db_name' => 'ibm_db',
                'db_user' => 'root',
                'db_password' => '',
            ]
        );

        Client::updateOrCreate(
            ['client_code' => 'hcl'],
            [
                'client_name' => 'HCL',
                'db_server' => '127.0.0.1',
                'db_port' => '3306',
                'db_name' => 'hcl_db',
                'db_user' => 'root',
                'db_password' => '',
            ]
        );

        Client::updateOrCreate(
            ['client_code' => 'infosys'],
            [
                'client_name' => 'Infosys',
                'db_server' => '127.0.0.1',
                'db_port' => '3306',
                'db_name' => 'infosys_db',
                'db_user' => 'root',
                'db_password' => '',
            ]
        );
    }
}
