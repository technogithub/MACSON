<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('unifi_vouchers') && !Schema::hasColumn('unifi_vouchers', 'sync_status')) {
            Schema::table('unifi_vouchers', function (Blueprint $table) {
                $table->enum('sync_status', ['synced', 'pending_create', 'pending_revoke'])->default('synced')->after('status')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('unifi_vouchers') && Schema::hasColumn('unifi_vouchers', 'sync_status')) {
            Schema::table('unifi_vouchers', function (Blueprint $table) {
                $table->dropColumn('sync_status');
            });
        }
    }
};
