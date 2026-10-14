<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel schema_version (Wajib ada untuk verifikasi integritas oleh Kea DHCP)
        // Versi skema hosts MySQL untuk Kea DHCP 2.4 - 2.6 adalah versi 3.0
        if (!Schema::hasTable('schema_version')) {
            DB::statement("
                CREATE TABLE `schema_version` (
                    `version` INT UNSIGNED NOT NULL,
                    `minor` INT UNSIGNED NOT NULL,
                    PRIMARY KEY (`version`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            DB::table('schema_version')->insertOrIgnore([
                'version' => 3,
                'minor'   => 0,
            ]);
        }

        // 2. Tabel hosts resmi Kea DHCPv4 Host Reservations
        if (!Schema::hasTable('hosts')) {
            DB::statement("
                CREATE TABLE `hosts` (
                    `host_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `dhcp_identifier` VARBINARY(128) NOT NULL,
                    `dhcp_identifier_type` TINYINT NOT NULL DEFAULT 0 COMMENT '0=hw-address (MAC)',
                    `dhcp4_subnet_id` INT UNSIGNED NULL,
                    `ipv4_address` INT UNSIGNED NULL,
                    `hostname` VARCHAR(255) NULL,
                    `dhcp4_client_classes` VARCHAR(255) NULL,
                    `dhcp4_next_server` INT UNSIGNED NULL,
                    `dhcp4_server_hostname` VARCHAR(64) NULL,
                    `dhcp4_boot_file_name` VARCHAR(128) NULL,
                    `user_context` TEXT NULL,
                    PRIMARY KEY (`host_id`),
                    KEY `key_dhcp4_ipv4_address` (`ipv4_address`),
                    KEY `key_dhcp_identifier` (`dhcp_identifier`, `dhcp_identifier_type`),
                    KEY `key_dhcp4_subnet_id` (`dhcp4_subnet_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 3. Migrasikan seluruh data existing dari dhcp_static_leases ke tabel hosts Kea
        if (Schema::hasTable('dhcp_static_leases')) {
            $existingLeases = DB::table('dhcp_static_leases')->where('is_active', true)->get();

            foreach ($existingLeases as $lease) {
                $cleanMac = strtoupper(str_replace([':', '-', '.'], '', trim($lease->mac_address)));
                if (strlen($cleanMac) !== 12 || !ctype_xdigit($cleanMac)) {
                    continue;
                }

                $ipLong = ip2long(trim($lease->ip_address));
                if ($ipLong === false) {
                    continue;
                }
                $unsignedIp = sprintf('%u', $ipLong);

                // Cek apakah sudah ada di tabel hosts
                $exists = DB::table('hosts')
                    ->whereRaw("dhcp_identifier = UNHEX(?)", [$cleanMac])
                    ->where('dhcp_identifier_type', 0)
                    ->exists();

                if (!$exists) {
                    DB::statement("
                        INSERT INTO `hosts` (
                            `dhcp_identifier`,
                            `dhcp_identifier_type`,
                            `dhcp4_subnet_id`,
                            `ipv4_address`,
                            `hostname`,
                            `user_context`
                        ) VALUES (
                            UNHEX(?),
                            0,
                            ?,
                            ?,
                            ?,
                            ?
                        )
                    ", [
                        $cleanMac,
                        $lease->dhcp_subnet_id ?: null,
                        $unsignedIp,
                        $lease->hostname ?: null,
                        json_encode(['source' => 'santafe_migrated', 'lease_id' => $lease->id])
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hosts');
        Schema::dropIfExists('schema_version');
    }
};
