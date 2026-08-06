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

        // 2. Today's Auth Logs
        $today = Carbon::today();
        $todayLogsCount = RadiusLog::whereDate('log_date', $today)->count();
        $totalAccept = RadiusLog::where('auth_result', 'ACCEPT')->count();
        $totalReject = RadiusLog::where('auth_result', 'REJECT')->count();

        // 3. Hourly Authentication Graph Data (Last 24 Hours)
        $hourlyStats = RadiusLog::select(
                DB::raw('HOUR(log_date) as hour'),
                DB::raw("SUM(CASE WHEN auth_result = 'ACCEPT' THEN 1 ELSE 0 END) as accepts"),
                DB::raw("SUM(CASE WHEN auth_result = 'REJECT' THEN 1 ELSE 0 END) as rejects")
            )
            ->whereDate('log_date', $today)
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
