<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        Schema::create('hosts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('hostname', 36)->unique();
            $table->ipAddress('ip');
            $table->jsonb('tags')->nullable();
            $table->timestamps();

            $table->index('tags')->algorithm('gin');
        });

        // hostname gin index
        DB::statement('
                CREATE INDEX hosts_hostname_index
                ON hosts
                USING gin (hostname gin_trgm_ops)
            ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hosts');
    }
};
