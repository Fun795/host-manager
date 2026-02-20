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
        Schema::create('operations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['rename']);
            $table->enum('status', ['pending','processing','done','failed']);
            $table->uuid('host_id');
            $table->jsonb('payload');
            $table->string('idempotency_key', 36)->unique()->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->foreign('host_id')->references('id')->on('hosts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
