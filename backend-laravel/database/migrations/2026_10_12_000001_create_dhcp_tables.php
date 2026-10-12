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
        // 1. Tabel Subnet & Pool DHCP
        Schema::create('dhcp_subnets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedSmallInteger('vlan_id')->nullable()->index();
            $table->string('subnet', 50)->unique()->comment('Contoh: 192.168.20.0/24');
            $table->string('pool_start', 45)->comment('Contoh: 192.168.20.50');
            $table->string('pool_end', 45)->comment('Contoh: 192.168.20.200');
            $table->string('gateway', 45)->comment('Router / Switch L3 gateway');
            $table->string('dns_servers', 255)->default('1.1.1.1, 8.8.8.8');
            $table->unsignedInteger('lease_time')->default(86400)->comment('Durasi sewa dalam detik');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tabel Static IP Reservations
        Schema::create('dhcp_static_leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dhcp_subnet_id')->nullable()->constrained('dhcp_subnets')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('mac_address', 17)->index();
            $table->string('ip_address', 45)->unique();
            $table->string('hostname', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['mac_address', 'dhcp_subnet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dhcp_static_leases');
        Schema::dropIfExists('dhcp_subnets');
    }
};
