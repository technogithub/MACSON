<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment('MACSON Network Access Control System is Online');
})->purpose('Display system message');

/**
 * Enterprise Log Pruning Command
 * Deletes RADIUS authentication audit logs older than N days (default 30 days) to prevent disk space saturation.
 */
Artisan::command('radius:prune-logs {--days=30 : Number of days to retain logs}', function () {
    $days = (int) $this->option('days');
    $deleted = DB::table('radius_log')
        ->where('log_date', '<', now()->subDays($days))
        ->delete();
    $this->info("Pruned {$deleted} RADIUS authentication logs older than {$days} days.");
})->purpose('Prune old RADIUS audit logs to save disk space');

// Schedule daily automated pruning at 02:00 AM
Schedule::command('radius:prune-logs --days=30')->dailyAt('02:00');

/**
 * UniFi Voucher Auto-Sync Command
 * Automatically syncs pending created or revoked vouchers to UniFi Controller when online
 */
Artisan::command('unifi:sync-vouchers', function (\App\Services\UniFiService $uniFiService) {
    $pendingStats = $uniFiService->syncPendingVouchers();
    $fullStats    = $uniFiService->syncAllVouchersFromUniFi();
    $this->info("UniFi Sync Completed. Imported: {$fullStats['imported']}, Status Updated: {$fullStats['updated']}, Pending Created: {$pendingStats['created']}, Pending Revoked: {$pendingStats['revoked']}");
})->purpose('Sync pending vouchers and update usage status with UniFi Controller');

// Schedule automatic background sync every 5 minutes
Schedule::command('unifi:sync-vouchers')->everyFiveMinutes();

