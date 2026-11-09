<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServerMonitorController extends Controller
{
    /**
     * Dedicated Server Monitor page
     */
    public function index()
    {
        return view('server-monitor.index');
    }

    /**
     * SSE Stream: broadcast server metrics in real-time
     */
    public function stream(): StreamedResponse
    {
        return response()->stream(function () {
            // Disable output buffering
            if (ob_get_level()) {
                ob_end_flush();
            }

            $iteration = 0;

            while (true) {
                // Check if client disconnected
                if (connection_aborted()) {
                    break;
                }

                $data = $this->collectMetrics($iteration);

                echo "data: " . json_encode($data) . "\n\n";

                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                $iteration++;
                sleep(2); // Update every 2 seconds
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * Collect all system metrics
     */
    private function collectMetrics(int $iteration): array
    {
        return [
            'cpu'       => $this->getCpuUsage(),
            'ram'       => $this->getRamUsage(),
            'disk'      => $this->getDiskUsage(),
            'network'   => $this->getNetworkIO($iteration),
            'docker'    => $this->getDockerContainers(),
            'uptime'    => $this->getUptime(),
            'timestamp' => now()->format('H:i:s'),
        ];
    }

    /**
     * CPU Usage % (reads /proc/stat twice with 100ms gap)
     */
    private function getCpuUsage(): array
    {
        try {
            $stat1 = file_get_contents('/proc/stat');
            usleep(100000); // 100ms
            $stat2 = file_get_contents('/proc/stat');

            $cpu1 = $this->parseCpuLine($stat1);
            $cpu2 = $this->parseCpuLine($stat2);

            $totalDiff = array_sum($cpu2) - array_sum($cpu1);
            $idleDiff  = $cpu2[3] - $cpu1[3];

            $usage = $totalDiff > 0
                ? round((1 - $idleDiff / $totalDiff) * 100, 1)
                : 0;

            return [
                'percent' => $usage,
                'cores'   => (int) shell_exec('nproc') ?: 1,
            ];
        } catch (\Exception $e) {
            return ['percent' => 0, 'cores' => 1];
        }
    }

    private function parseCpuLine(string $stat): array
    {
        preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $stat, $m);
        return array_map('intval', array_slice($m, 1));
    }

    /**
     * RAM Usage from /proc/meminfo
     */
    private function getRamUsage(): array
    {
        try {
            $meminfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);

            $totalKb     = (int) ($total[1] ?? 0);
            $availableKb = (int) ($available[1] ?? 0);
            $usedKb      = $totalKb - $availableKb;

            $percent = $totalKb > 0 ? round(($usedKb / $totalKb) * 100, 1) : 0;

            return [
                'percent'   => $percent,
                'used_mb'   => round($usedKb / 1024, 0),
                'total_mb'  => round($totalKb / 1024, 0),
                'free_mb'   => round($availableKb / 1024, 0),
            ];
        } catch (\Exception $e) {
            return ['percent' => 0, 'used_mb' => 0, 'total_mb' => 0, 'free_mb' => 0];
        }
    }

    /**
     * Disk Usage from df
     */
    private function getDiskUsage(): array
    {
        try {
            $output = shell_exec("df -B1 / 2>/dev/null | tail -1");
            if (!$output) return ['percent' => 0, 'used_gb' => 0, 'total_gb' => 0, 'free_gb' => 0];

            $parts = preg_split('/\s+/', trim($output));
            $total = (int) ($parts[1] ?? 0);
            $used  = (int) ($parts[2] ?? 0);
            $free  = (int) ($parts[3] ?? 0);

            $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0;

            return [
                'percent' => $percent,
                'used_gb' => round($used / (1024 ** 3), 1),
                'total_gb'=> round($total / (1024 ** 3), 1),
                'free_gb' => round($free / (1024 ** 3), 1),
            ];
        } catch (\Exception $e) {
            return ['percent' => 0, 'used_gb' => 0, 'total_gb' => 0, 'free_gb' => 0];
        }
    }

    /**
     * Network I/O bytes/s from /proc/net/dev (eth0 or first active interface)
     */
    private function getNetworkIO(int $iteration): array
    {
        static $prevRx = null, $prevTx = null, $prevTime = null;

        try {
            $netdev = file_get_contents('/proc/net/dev');
            $lines  = explode("\n", $netdev);
            $rx = $tx = 0;

            foreach ($lines as $line) {
                // Skip lo (loopback) and header lines
                if (preg_match('/^\s*(eth\d+|ens\d+|enp\d+s\d+|wlan\d+|bond\d+):\s+(\d+)\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+(\d+)/', $line, $m)) {
                    $rx += (int) $m[2];
                    $tx += (int) $m[3];
                }
            }

            $now      = microtime(true);
            $rxRate   = 0;
            $txRate   = 0;

            if ($prevRx !== null && $prevTime !== null) {
                $elapsed = $now - $prevTime;
                if ($elapsed > 0) {
                    $rxRate = max(0, ($rx - $prevRx) / $elapsed);
                    $txRate = max(0, ($tx - $prevTx) / $elapsed);
                }
            }

            $prevRx   = $rx;
            $prevTx   = $tx;
            $prevTime = $now;

            return [
                'rx_bytes_s' => (int) $rxRate,
                'tx_bytes_s' => (int) $txRate,
                'rx_human'   => $this->humanBytes($rxRate) . '/s',
                'tx_human'   => $this->humanBytes($txRate) . '/s',
            ];
        } catch (\Exception $e) {
            return ['rx_bytes_s' => 0, 'tx_bytes_s' => 0, 'rx_human' => '0 B/s', 'tx_human' => '0 B/s'];
        }
    }

    /**
     * Docker container status (name, status, running)
     */
    private function getDockerContainers(): array
    {
        try {
            $output = shell_exec('docker ps -a --format "{{.Names}}|{{.Status}}|{{.State}}" 2>/dev/null');
            if (!$output) return [];

            $containers = [];
            foreach (explode("\n", trim($output)) as $line) {
                if (!$line) continue;
                [$name, $status, $state] = array_pad(explode('|', $line), 3, 'unknown');
                $containers[] = [
                    'name'    => $name,
                    'status'  => $status,
                    'running' => strtolower($state) === 'running',
                ];
            }

            return $containers;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Server uptime from /proc/uptime
     */
    private function getUptime(): string
    {
        try {
            $uptimeRaw = (float) explode(' ', file_get_contents('/proc/uptime'))[0];
            $days      = floor($uptimeRaw / 86400);
            $hours     = floor(($uptimeRaw % 86400) / 3600);
            $minutes   = floor(($uptimeRaw % 3600) / 60);

            if ($days > 0) return "{$days}d {$hours}h {$minutes}m";
            if ($hours > 0) return "{$hours}h {$minutes}m";
            return "{$minutes}m";
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Convert bytes to human-readable string
     */
    private function humanBytes(float $bytes): string
    {
        if ($bytes >= 1024 ** 3) return round($bytes / (1024 ** 3), 2) . ' GB';
        if ($bytes >= 1024 ** 2) return round($bytes / (1024 ** 2), 2) . ' MB';
        if ($bytes >= 1024)      return round($bytes / 1024, 1) . ' KB';
        return (int) $bytes . ' B';
    }
}
