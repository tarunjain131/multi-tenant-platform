<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('landing')->create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('client_code')->unique();
            $table->string('db_server');
            $table->string('db_port');
            $table->string('db_name')->unique();
            $table->string('db_user');
            $table->string('db_password')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landing')->dropIfExists('clients');
    }
};
