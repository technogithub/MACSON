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
        // 1. Create guest_blacklists table
        if (!Schema::hasTable('guest_blacklists')) {
            Schema::create('guest_blacklists', function (Blueprint $table) {
                $table->id();
                $table->string('mac_address', 17)->unique()->comment('Sanitized MAC: AA:BB:CC:DD:EE:FF');
                $table->string('device_name', 100)->nullable()->comment('Device brand / hostname if detected');
                $table->string('reason', 255)->nullable()->comment('Reason for blacklisting / ban');
                $table->unsignedBigInteger('blocked_by')->nullable()->comment('User ID who blocked this device');
                $table->timestamp('blocked_at')->useCurrent();
                $table->timestamp('expires_at')->nullable()->comment('NULL = Permanent block');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['mac_address', 'is_active']);
                $table->index('expires_at');
            });
        }

        // 2. Add auth_mode to ssids table
        if (Schema::hasTable('ssids') && !Schema::hasColumn('ssids', 'auth_mode')) {
            Schema::table('ssids', function (Blueprint $table) {
                $table->enum('auth_mode', ['whitelist', 'blacklist'])
                      ->default('whitelist')
                      ->after('status')
                      ->comment('whitelist = corp/staff, blacklist = guest/tamu');
                $table->index('auth_mode');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_blacklists');

        if (Schema::hasTable('ssids') && Schema::hasColumn('ssids', 'auth_mode')) {
            Schema::table('ssids', function (Blueprint $table) {
                $table->dropColumn('auth_mode');
            });
        }
    }
};
