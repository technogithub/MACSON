<?php

namespace App\Services;

use App\Models\DhcpSubnet;
use App\Models\DhcpStaticLease;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DhcpConfigService
{
    /**
     * Path file konfigurasi Kea DHCPv4 (dapat disesuaikan via env atau path default)
     */
    protected string $configPath;
    protected string $leasesPath;

    public function __construct()
    {
        $this->configPath = env('KEA_CONFIG_PATH', base_path('../dhcp/kea-dhcp4.conf'));
        $this->leasesPath = env('KEA_LEASES_PATH', base_path('../dhcp/leases/kea-leases4.csv'));
    }

    /**
     * Bangun struktur JSON konfigurasi Kea DHCPv4 dari database
     */
    public function buildConfigArray(): array
    {
        $subnets = DhcpSubnet::with(['staticLeases' => function ($q) {
            $q->where('is_active', true);
        }])->where('is_active', true)->get();

        $subnetList = [];

        foreach ($subnets as $subnet) {
            $options = [];

            // Option 3: Default Router / Gateway
            if (!empty($subnet->gateway)) {
                $options[] = [
                    'name' => 'routers',
                    'data' => $subnet->gateway,
                ];
            }

            // Option 6: DNS Servers
            if (!empty($subnet->dns_servers)) {
                $dnsClean = implode(', ', array_map('trim', explode(',', $subnet->dns_servers)));
                $options[] = [
                    'name' => 'domain-name-servers',
                    'data' => $dnsClean,
                ];
            }

            // Reservations (Static IP)
            $reservations = [];
            foreach ($subnet->staticLeases as $lease) {
                $resItem = [
                    'hw-address' => strtolower($lease->mac_address),
                    'ip-address' => $lease->ip_address,
                ];

                if (!empty($lease->hostname)) {
                    $resItem['hostname'] = $lease->hostname;
                }

                $reservations[] = $resItem;
            }

            $subnetItem = [
                'id'             => $subnet->id,
                'subnet'         => $subnet->subnet,
                'pools'          => [
                    ['pool' => "{$subnet->pool_start} - {$subnet->pool_end}"]
                ],
                'valid-lifetime' => $subnet->lease_time ?: 86400,
                'option-data'    => $options,
            ];

            if (!empty($reservations)) {
                $subnetItem['reservations'] = $reservations;
            }

            $subnetList[] = $subnetItem;
        }

        return [
            'Dhcp4' => [
                'interfaces-config' => [
                    'interfaces' => ['*'],
                    'dhcp-socket-type' => 'raw'
                ],
                'lease-database' => [
                    'type'     => 'memfile',
                    'persist'  => true,
                    'name'     => '/var/lib/kea/kea-leases4.csv',
                    'lfc-interval' => 3600
                ],
                'valid-lifetime' => 86400,
                'renew-timer'    => 43200,
                'rebind-timer'   => 75600,
                'subnet4'        => $subnetList,
                'loggers'        => [
                    [
                        'name' => 'kea-dhcp4',
                        'output_options' => [
                            [
                                'output' => 'stdout',
                                'pattern' => '%D{%Y-%m-%d %H:%M:%S.%q} %-5p [%c/%i] %m\n'
                            ]
                        ],
                        'severity' => 'INFO'
                    ]
                ]
            ]
        ];
    }

    /**
     * Tulis konfigurasi ke file disk
     */
    public function syncConfigFile(): bool
    {
        try {
            $configData = $this->buildConfigArray();
            $jsonString = json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $dir = dirname($this->configPath);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true, true);
            }

            File::put($this->configPath, $jsonString);

            // Opsional: Reload container Kea jika aktif
            $this->reloadKeaService();

            return true;
        } catch (\Throwable $e) {
            Log::error("Gagal sinkronisasi config Kea DHCP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Memicu reload Kea DHCP server tanpa restart container (zero-downtime)
     */
    public function reloadKeaService(): void
    {
        try {
            // Jika berjalan di docker, kirim sinyal SIGHUP ke proses kea di container radius_dhcp
            if (shell_exec('which docker')) {
                shell_exec('docker kill -s HUP radius_dhcp 2>/dev/null');
            }
        } catch (\Throwable $e) {
            // abaikan jika container belum terpasang
        }
    }

    /**
     * Baca dan parse active leases dari file CSV leases Kea
     */
    public function getActiveLeases(): array
    {
        if (!File::exists($this->leasesPath)) {
            return [];
        }

        $lines = file($this->leasesPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines) || count($lines) <= 1) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $now = time();
        $leases = [];

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) < 5) continue;

            $record = array_combine(array_slice($headers, 0, count($row)), $row);
            
            $ip       = $record['address'] ?? '-';
            $mac      = strtoupper($record['hwaddr'] ?? '-');
            $expire   = (int)($record['expire'] ?? 0);
            $hostname = $record['hostname'] ?? '-';
            $subnetId = (int)($record['subnet_id'] ?? 0);

            // Filter hanya lease yang belum expire
            $isActive = $expire > $now;

            $leases[] = [
                'ip'         => $ip,
                'mac'        => $mac,
                'hostname'   => $hostname !== '' ? $hostname : '-',
                'expire_at'  => $expire > 0 ? date('Y-m-d H:i:s', $expire) : '-',
                'is_active'  => $isActive,
                'subnet_id'  => $subnetId,
            ];
        }

        // Urutkan dari yang terbaru
        usort($leases, fn($a, $b) => strcmp($b['expire_at'], $a['expire_at']));

        return $leases;
    }
}
