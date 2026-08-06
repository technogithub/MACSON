<?php

namespace App\Http\Controllers;

use App\Models\RadiusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class LogController extends Controller
{
    /**
     * Display Authentication & Accounting Radius Logs with Filters
     */
    public function index(Request $request)
    {
        $query = RadiusLog::query();

        // Search Filter (MAC, Username, NAS IP, Reason)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('mac_address', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('nas_ip', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        // Auth Result Filter (ACCEPT / REJECT)
        if ($request->filled('result') && $request->result !== 'all') {
            $query->where('auth_result', $request->result);
        }

        // Date Range Filter
        if ($request->filled('start_date')) {
            $query->whereDate('log_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('log_date', '<=', $request->end_date);
        }

        $logs = $query->orderBy('log_date', 'desc')->paginate(15)->withQueryString();

        return view('logs.index', compact('logs'));
    }

    /**
     * Clear all audit logs
     */
    public function clear()
    {
        RadiusLog::truncate();
        return redirect()->route('logs.index')->with('success', 'All RADIUS audit logs cleared successfully.');
    }

    /**
     * Export Radius Logs to CSV/Excel
     */
    public function exportExcel(Request $request)
    {
        $query = RadiusLog::query();

        if ($request->filled('start_date')) {
            $query->whereDate('log_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('log_date', '<=', $request->end_date);
        }

        $logs = $query->orderBy('log_date', 'desc')->get();
        $fileName = 'radius_logs_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Date / Time', 'MAC Address', 'NAS IP', 'Result', 'Username', 'Reason']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->log_date,
                    $log->mac_address,
                    $log->nas_ip,
                    $log->auth_result,
                    $log->username,
                    $log->reason
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
