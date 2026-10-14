<?php

namespace App\Services;

use App\Models\DhcpSubnet;
use App\Models\DhcpStaticLease;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DhcpConfigService
{
    /**
     * Path file konfigurasi Kea DHCPv4 (dapat disesuaikan via env atau path default)
     */
    protected string $configPath;
    protected string $leasesPath;

    public function __construct()
    {
        // Gunakan storage_path() agar www-data selalu punya akses tulis
        $this->configPath = env('KEA_CONFIG_PATH', storage_path('dhcp/kea-dhcp4.conf'));
        $this->leasesPath = env('KEA_LEASES_PATH', storage_path('dhcp/leases/kea-leases4.csv'));
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

            $pools = [];
            if (!empty($subnet->pool_start) && !empty($subnet->pool_end)) {
                $pools[] = ['pool' => "{$subnet->pool_start} - {$subnet->pool_end}"];
            }

            $subnetItem = [
                'id'             => $subnet->id,
                'subnet'         => $subnet->subnet,
                'pools'          => $pools,
                'valid-lifetime' => $subnet->lease_time ?: 86400,
                'option-data'    => $options,
            ];

            if (!empty($reservations)) {
                $subnetItem['reservations'] = $reservations;
            }

            $subnetList[] = $subnetItem;
        }

        $dbHost = env('KEA_DB_HOST', '127.0.0.1');
        $dbName = config('database.connections.mysql.database', 'radius');
        $dbUser = config('database.connections.mysql.username', 'radius_user');
        $dbPass = config('database.connections.mysql.password', 'radius_password');
        $dbPort = (int)config('database.connections.mysql.port', 3306);

        return [
            'Dhcp4' => [
                'interfaces-config' => [
                    'interfaces' => ['*'],
                    'dhcp-socket-type' => 'raw'
                ],
                'hosts-database' => [
                    'type'     => 'mysql',
                    'name'     => $dbName,
                    'host'     => $dbHost,
                    'port'     => $dbPort,
                    'user'     => $dbUser,
                    'password' => $dbPass,
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
     * Baca dan parse active leases dari file CSV leases Kea (termasuk file rotasi LFC .2 dan .completed)
     */
    public function getActiveLeases(): array
    {
        $leasesDir = dirname($this->leasesPath);
        if (!File::isDirectory($leasesDir)) {
            return [];
        }

        // Kumpulkan semua file lease yang ada (file utama .csv, rotasi .csv.2, dan .csv.completed)
        $potentialFiles = [
            $this->leasesPath . '.2',
            $this->leasesPath . '.completed',
            $this->leasesPath,
        ];

        $now = time();
        $leasesByIp = [];

        foreach ($potentialFiles as $filePath) {
            if (!File::exists($filePath)) {
                continue;
            }

            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (empty($lines) || count($lines) <= 1) {
                continue;
            }

            $headers = str_getcsv(array_shift($lines));

            foreach ($lines as $line) {
                $row = str_getcsv($line);
                if (count($row) < 5) continue;

                $record = array_combine(array_slice($headers, 0, count($row)), $row);
                
                $ip       = trim($record['address'] ?? '');
                if (empty($ip) || $ip === '-' || !filter_var($ip, FILTER_VALIDATE_IP)) {
                    continue;
                }

                $mac      = strtoupper(trim($record['hwaddr'] ?? '-'));
                $expire   = (int)($record['expire'] ?? 0);
                $hostname = trim($record['hostname'] ?? '');
                $subnetId = (int)($record['subnet_id'] ?? 0);
                $state    = isset($record['state']) ? (int)$record['state'] : 0;

            // Di Kea memfile CSV: state 0 = default (active lease)
            // State 1 = declined, state 2 = expired-reclaimed
            $isActive = ($state === 0 && $expire > $now);

            // Jika baris kronologis baru ini bukan active lease (misal expired / reclaimed / declined)
            if (!$isActive) {
                // Jika sebelumnya ada di list dan baris baru ini memiliki expire >= atau sama
                if (isset($leasesByIp[$ip]) && $expire >= $leasesByIp[$ip]['expire_raw']) {
                    unset($leasesByIp[$ip]);
                }
                continue;
            }

            // Kea CSV adalah append log: ambil atau update entri jika lebih baru
            if (!isset($leasesByIp[$ip]) || $expire >= $leasesByIp[$ip]['expire_raw']) {
                $leasesByIp[$ip] = [
                    'ip'         => $ip,
                    'mac'        => $mac,
                    'hostname'   => ($hostname !== '' && $hostname !== '(none)') ? $hostname : '-',
                    'expire_at'  => date('Y-m-d H:i:s', $expire),
                    'expire_raw' => $expire,
                    'is_active'  => true,
                    'subnet_id'  => $subnetId,
                ];
            }
        }

        $leases = array_values($leasesByIp);

        // Urutkan dari expiration yang paling akhir (paling baru di-renew / masa aktif terlama)
        usort($leases, fn($a, $b) => $b['expire_raw'] <=> $a['expire_raw']);

        return $leases;
    }

    /**
     * Simpan / Perbarui Reservasi Static IP langsung ke tabel Kea DHCP `hosts` (MySQL Backend)
     */
    public function saveHostToKeaDb(string $mac, string $ip, ?int $subnetId = null, ?string $hostname = null, ?int $leaseId = null): bool
    {
        try {
            $cleanMac = strtoupper(str_replace([':', '-', '.'], '', trim($mac)));
            if (strlen($cleanMac) !== 12 || !ctype_xdigit($cleanMac)) {
                return false;
            }

            $ipLong = ip2long(trim($ip));
            if ($ipLong === false) {
                return false;
            }
            $unsignedIp = sprintf('%u', $ipLong);

            // Cek apakah tabel hosts ada di database
            if (!\Illuminate\Support\Facades\Schema::hasTable('hosts')) {
                return false;
            }

            // Hapus reservasi lama untuk MAC atau IP yang sama agar tidak duplikat di tabel Kea
            DB::statement("DELETE FROM `hosts` WHERE `dhcp_identifier` = UNHEX(?) AND `dhcp_identifier_type` = 0", [$cleanMac]);
            DB::table('hosts')->where('ipv4_address', $unsignedIp)->delete();

            // Insert data baru ke tabel hosts Kea
            DB::statement("
                INSERT INTO `hosts` (
                    `dhcp_identifier`,
                    `dhcp_identifier_type`,
                    `dhcp4_subnet_id`,
                    `ipv4_address`,
                    `hostname`,
                    `user_context`
                ) VALUES (
                    UNHEX(?),
                    0,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ", [
                $cleanMac,
                $subnetId ?: null,
                $unsignedIp,
                $hostname ?: null,
                json_encode(['source' => 'santafe_ui', 'lease_id' => $leaseId, 'updated_at' => now()->toIso8601String()])
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning("Gagal menyimpan host ke tabel Kea MySQL: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hapus Reservasi Static IP dari tabel Kea DHCP `hosts`
     */
    public function deleteHostFromKeaDb(string $mac): bool
    {
        try {
            $cleanMac = strtoupper(str_replace([':', '-', '.'], '', trim($mac)));
            if (strlen($cleanMac) !== 12 || !ctype_xdigit($cleanMac)) {
                return false;
            }

            if (!\Illuminate\Support\Facades\Schema::hasTable('hosts')) {
                return false;
            }

            DB::statement("DELETE FROM `hosts` WHERE `dhcp_identifier` = UNHEX(?) AND `dhcp_identifier_type` = 0", [$cleanMac]);
            return true;
        } catch (\Throwable $e) {
            Log::warning("Gagal menghapus host dari tabel Kea MySQL: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sinkronkan ulang seluruh Static Lease yang aktif ke tabel `hosts` Kea
     */
    public function syncAllStaticLeasesToKeaDb(): int
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('hosts')) {
                return 0;
            }

            $leases = DhcpStaticLease::where('is_active', true)->get();
            $synced = 0;

            foreach ($leases as $lease) {
                if ($this->saveHostToKeaDb($lease->mac_address, $lease->ip_address, $lease->dhcp_subnet_id, $lease->hostname, $lease->id)) {
                    $synced++;
                }
            }

            return $synced;
        } catch (\Throwable $e) {
            Log::error("Gagal sync seluruh static leases ke Kea DB: " . $e->getMessage());
            return 0;
        }
    }
}
