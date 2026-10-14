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
        Schema::table('dhcp_subnets', function (Blueprint $table) {
            $table->string('pool_start', 45)->nullable()->change();
            $table->string('pool_end', 45)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dhcp_subnets', function (Blueprint $table) {
            $table->string('pool_start', 45)->nullable(false)->change();
            $table->string('pool_end', 45)->nullable(false)->change();
        });
    }
};
