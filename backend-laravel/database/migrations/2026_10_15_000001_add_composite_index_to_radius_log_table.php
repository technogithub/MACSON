<?php

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
        Schema::table('radius_log', function (Blueprint $table) {
            // Composite index for fast dashboard metrics and hourly charts
            $table->index(['log_date', 'auth_result'], 'idx_log_date_auth_result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('radius_log', function (Blueprint $table) {
            $table->dropIndex('idx_log_date_auth_result');
        });
    }
};
