<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\RadiusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. MAC Device Metrics
        $totalActive = Device::where('status', 'active')->count();
        $totalInactive = Device::where('status', 'inactive')->count();

        // 2. Today's Auth Logs (Optimized using index range)
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        // Single aggregation query for today's logs (1 scan using idx_log_date instead of 3 full table scans)
        $todayStats = RadiusLog::select(
                DB::raw('COUNT(*) as total_today'),
                DB::raw("SUM(CASE WHEN auth_result = 'ACCEPT' THEN 1 ELSE 0 END) as total_accept"),
                DB::raw("SUM(CASE WHEN auth_result = 'REJECT' THEN 1 ELSE 0 END) as total_reject")
            )
            ->whereBetween('log_date', [$todayStart, $todayEnd])
            ->first();

        $todayLogsCount = (int) ($todayStats->total_today ?? 0);
        $totalAccept = (int) ($todayStats->total_accept ?? 0);
        $totalReject = (int) ($todayStats->total_reject ?? 0);

        // 3. Hourly Authentication Graph Data (Today)
        $hourlyStats = RadiusLog::select(
                DB::raw('HOUR(log_date) as hour'),
                DB::raw("SUM(CASE WHEN auth_result = 'ACCEPT' THEN 1 ELSE 0 END) as accepts"),
                DB::raw("SUM(CASE WHEN auth_result = 'REJECT' THEN 1 ELSE 0 END) as rejects")
            )
            ->whereBetween('log_date', [$todayStart, $todayEnd])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $labels = [];
        $acceptData = [];
        $rejectData = [];

        for ($i = 0; $i < 24; $i++) {
            $labels[] = sprintf('%02d:00', $i);
            $stat = $hourlyStats->firstWhere('hour', $i);
            $acceptData[] = $stat ? (int) $stat->accepts : 0;
            $rejectData[] = $stat ? (int) $stat->rejects : 0;
        }

        // 4. Recent Authentication Activity Feed
        $recentLogs = RadiusLog::orderBy('log_date', 'desc')->take(8)->get();

        return view('dashboard', compact(
            'totalActive',
            'totalInactive',
            'todayLogsCount',
            'totalAccept',
            'totalReject',
            'labels',
            'acceptData',
            'rejectData',
            'recentLogs'
        ));
    }
}
