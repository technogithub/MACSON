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

