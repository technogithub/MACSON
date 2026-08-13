<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('unifi_vouchers') && !Schema::hasColumn('unifi_vouchers', 'unifi_id')) {
            Schema::table('unifi_vouchers', function (Blueprint $table) {
                $table->string('unifi_id', 50)->nullable()->after('id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('unifi_vouchers') && Schema::hasColumn('unifi_vouchers', 'unifi_id')) {
            Schema::table('unifi_vouchers', function (Blueprint $table) {
                $table->dropColumn('unifi_id');
            });
        }
    }
};
