<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unifi_configs', function (Blueprint $table) {
            $table->id();
            $table->string('controller_url')->default('https://127.0.0.1:8443');
            $table->string('site_id')->default('default');
            $table->string('username')->default('admin');
            $table->string('password')->default('password');
            $table->boolean('verify_ssl')->default(false);
            $table->timestamps();
        });

        Schema::create('unifi_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('unifi_id', 50)->nullable()->index();
            $table->string('code', 20)->index();
            $table->integer('duration_minutes');
            $table->integer('quota_mb')->nullable();
            $table->integer('down_kbps')->nullable();
            $table->integer('up_kbps')->nullable();
            $table->integer('use_limit')->default(1);
            $table->integer('used_count')->default(0);
            $table->string('note')->nullable();
            $table->string('batch_id', 50)->nullable()->index();
            $table->enum('status', ['unused', 'used', 'expired', 'revoked'])->default('unused');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unifi_vouchers');
        Schema::dropIfExists('unifi_configs');
    }
};
