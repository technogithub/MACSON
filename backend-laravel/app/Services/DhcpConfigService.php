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
                'control-socket' => [
                    'socket-type' => 'unix',
                    'socket-name' => '/var/run/kea/kea4-ctrl-socket',
                ],
                // Hook lease_cmds: aktifkan perintah lease4-del, lease4-wipe, lease4-get, dll
                // agar bisa membersihkan lease dari memori Kea TANPA harus restart container
                'hooks-libraries' => [
                    [
                        'library' => '/usr/lib/x86_64-linux-gnu/kea/hooks/libdhcp_lease_cmds.so',
                    ],
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

            $headerLine = array_shift($lines);
            if (!$headerLine) continue;
            $headers = str_getcsv($headerLine);

            foreach ($lines as $line) {
                $row = str_getcsv($line);
                if (empty($row) || count($row) < 5) continue;

                $record = [];
                foreach ($headers as $idx => $headerName) {
                    $record[trim($headerName)] = isset($row[$idx]) ? trim($row[$idx]) : '';
                }
                
                $ip       = trim($record['address'] ?? '');
                if (empty($ip) || $ip === '-' || !filter_var($ip, FILTER_VALIDATE_IP)) {
                    continue;
                }

                $mac      = strtoupper(trim($record['hwaddr'] ?? '-'));
                $expire   = (int)($record['expire'] ?? 0);
                $hostname = trim($record['hostname'] ?? '');
                $subnetId = (int)($record['subnet_id'] ?? 0);
                $state    = isset($record['state']) ? (int)$record['state'] : 0;

            // Di Kea memfile CSV:
            // State 0 = default (active lease)
            // State 1 = declined
            // State 2 = expired-reclaimed
            // State 3 = released
            $isActive = ($state === 0 && $expire > $now);

            // Jika baris kronologis baru ini bukan active lease (misal expired / reclaimed / declined / released)
            if (!$isActive) {
                // Jika sebelumnya ada di list dan baris baru ini menandai berakhirnya lease
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
    }

        $leases = array_values($leasesByIp);

        // Urutkan dari expiration yang paling akhir (paling baru di-renew / masa aktif terlama)
        usort($leases, fn($a, $b) => $b['expire_raw'] <=> $a['expire_raw']);

        return $leases;
    }

    /**
     * Bersihkan dynamic leases dari file CSV Kea DHCP.
     *
     * Kasus A (subnetId=null): Bersihkan semua file rotasi & tulis CSV kosong, lalu reload/restart Kea
     * Kasus B (subnetId tertentu): Tulis baris release (state 3) / filter CSV dan sinkronkan dengan Kea
     */
    public function clearLeases(?int $subnetId = null): int
    {
        // 1. Usahakan berikan akses baca-tulis ke folder leases
        @chmod(dirname($this->leasesPath), 0777);
        @chmod($this->leasesPath, 0666);
        @chmod($this->leasesPath . '.2', 0666);
        @chmod($this->leasesPath . '.completed', 0666);

        // Ubah permission via container selagi aktif
        try {
            shell_exec("docker exec radius_dhcp chmod -R 777 /var/lib/kea 2>/dev/null");
        } catch (\Throwable $e) {}

        $defaultHeader = "address,hwaddr,client_id,valid_lifetime,expire,subnet_id,fqdn_fwd,fqdn_rev,hostname,state,user_context\n";
        $clearedCount  = 0;

        // Ambil data active leases saat ini
        $currentLeases = $this->getActiveLeases();

        // =====================================================================
        // KASUS A: Bersihkan SEMUA lease di semua subnet
        // =====================================================================
        if ($subnetId === null) {
            $clearedCount = count($currentLeases);

            // Coba kirim lease4-wipe via control socket jika tersedia
            $this->sendKeaControlCommand([
                'command'   => 'lease4-wipe',
                'arguments' => ['subnet-id' => 0],
            ]);

            // Bersihkan file rotasi sebelum stop agar tidak terbaca lagi
            $this->removeKeaLeaseFile($this->leasesPath . '.2');
            $this->removeKeaLeaseFile($this->leasesPath . '.completed');

            // Hentikan Kea agar memori ter-flush
            $this->stopKeaService();

            // Tulis ulang file utama dengan header kosong saja
            $this->writeKeaLeasesFile($defaultHeader);

            // Bersihkan lagi file rotasi jika Kea sempat mendump saat shutdown
            $this->removeKeaLeaseFile($this->leasesPath . '.2');
            $this->removeKeaLeaseFile($this->leasesPath . '.completed');

            // Jalankan kembali Kea
            $this->startKeaService();

            return $clearedCount;
        }

        // =====================================================================
        // KASUS B: Bersihkan lease per subnet tertentu
        // =====================================================================
        $subnet    = DhcpSubnet::find($subnetId);
        $startLong = (!empty($subnet->pool_start)) ? ip2long(trim($subnet->pool_start)) : false;
        $endLong   = (!empty($subnet->pool_end))   ? ip2long(trim($subnet->pool_end))   : false;

        $networkLong   = false;
        $broadcastLong = false;
        if (!empty($subnet->subnet) && str_contains($subnet->subnet, '/')) {
            [$baseIpStr, $prefixStr] = explode('/', trim($subnet->subnet), 2);
            $baseLong = ip2long(trim($baseIpStr));
            $prefix   = (int)trim($prefixStr);
            if ($baseLong !== false && $prefix >= 8 && $prefix <= 30) {
                $mask          = $prefix === 0 ? 0 : (-1 << (32 - $prefix));
                $networkLong   = $baseLong & $mask;
                $broadcastLong = $networkLong | (~$mask & 0xFFFFFFFF);
            }
        }

        // Pisahkan lease yang dihapus dan lease yang dipertahankan
        $leasesToDelete = [];
        $remainingRows  = [];
        $releaseAppendRows = [];
        $now = time();

        foreach ($currentLeases as $l) {
            $ip          = $l['ip'];
            $rowSubnetId = (int)($l['subnet_id'] ?? 0);

            $matches = false;
            if ($rowSubnetId === $subnetId) {
                $matches = true;
            } elseif (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
                $ipLong = ip2long($ip);
                if ($startLong !== false && $endLong !== false
                    && $ipLong >= min($startLong, $endLong)
                    && $ipLong <= max($startLong, $endLong)) {
                    $matches = true;
                } elseif ($networkLong !== false && $broadcastLong !== false
                    && $ipLong >= $networkLong && $ipLong <= $broadcastLong) {
                    $matches = true;
                }
            }

            $rawMac = strtolower(str_replace([':', '-', '.'], '', $l['mac']));

            if ($matches) {
                $clearedCount++;
                $leasesToDelete[] = $ip;

                // Siapkan baris 'released' (state 3) dengan expire = now agar Kea & parser tahu lease ini sudah dicabut
                $releaseAppendRows[] = implode(',', [
                    $l['ip'],
                    $rawMac,
                    '',       // client_id
                    '86400',  // valid_lifetime
                    $now,     // expire sekarang (sudah lewat)
                    $l['subnet_id'],
                    '',       // fqdn_fwd
                    '',       // fqdn_rev
                    ($l['hostname'] !== '-' ? $l['hostname'] : ''),
                    '3',      // state = 3 (RELEASED)
                    '',       // user_context
                ]);
            } else {
                // Simpan baris lease subnet lain yang tidak dihapus
                $remainingRows[] = implode(',', [
                    $l['ip'],
                    $rawMac,
                    '',       // client_id
                    '86400',  // valid_lifetime
                    $l['expire_raw'],
                    $l['subnet_id'],
                    '',       // fqdn_fwd
                    '',       // fqdn_rev
                    ($l['hostname'] !== '-' ? $l['hostname'] : ''),
                    '0',      // state = assigned
                    '',       // user_context
                ]);
            }
        }

        if ($clearedCount === 0) {
            return 0;
        }

        // 1. Coba kirim lease4-del via control socket ke container yang sedang aktif
        $socketUsed = false;
        $testResponse = $this->sendKeaControlCommand(['command' => 'list-commands']);
        if ($testResponse && str_contains($testResponse, 'lease4-del')) {
            foreach ($leasesToDelete as $ip) {
                $this->sendKeaControlCommand([
                    'command'   => 'lease4-del',
                    'arguments' => ['ip-address' => $ip],
                ]);
            }
            $socketUsed = true;
        }

        // 2. STOP Kea DHCP container SEBELUM memodifikasi file CSV!
        // Ini SANGAT KRUSIAL: Jika Kea dimatikan setelah file CSV ditulis,
        // saat shutdown Kea akan men-dump ulang seluruh lease dari RAM-nya kembali ke file CSV disk!
        $this->stopKeaService();

        // 3. Tulis data ke CSV tanpa lease subnet yang dihapus:
        $newContent = $defaultHeader;
        if (!empty($remainingRows)) {
            $newContent .= implode("\n", $remainingRows) . "\n";
        }

        // Tulis ulang file leases CSV utama
        $this->writeKeaLeasesFile($newContent);

        // Hapus file rotasi lama (.2 dan .completed) agar parser getActiveLeases tidak membaca lease lama lagi
        $this->removeKeaLeaseFile($this->leasesPath . '.2');
        $this->removeKeaLeaseFile($this->leasesPath . '.completed');

        // 4. NYALAKAN KEMBALI container radius_dhcp
        Log::info("clearLeases: Menyalakan kembali Kea DHCP setelah mengosongkan {$clearedCount} active dynamic leases untuk Subnet ID {$subnetId}.");
        $this->startKeaService();

        return $clearedCount;
    }

    /**
     * Tulis ke file kea-leases4.csv secara aman dengan fallback Docker exec jika permission denied
     */
    private function writeKeaLeasesFile(string $content): void
    {
        try {
            @chmod($this->leasesPath, 0666);
            File::put($this->leasesPath, $content);
            @chmod($this->leasesPath, 0666);
            return;
        } catch (\Throwable $e) {
            Log::warning("File::put kea-leases4.csv gagal, mencoba fallback docker: " . $e->getMessage());
        }

        // Fallback: Tulis langsung melalui container radius_dhcp (akses root ke volume)
        try {
            $escaped = escapeshellarg($content);
            shell_exec("docker exec radius_dhcp sh -c \"printf %s {$escaped} > /var/lib/kea/kea-leases4.csv\" 2>/dev/null");
            shell_exec("docker exec radius_dhcp chmod 666 /var/lib/kea/kea-leases4.csv 2>/dev/null");
        } catch (\Throwable $e) {
            Log::error("Gagal fallback tulis kea-leases4.csv: " . $e->getMessage());
        }
    }

    /**
     * Hapus file rotasi kea-leases4.csv.2 / .completed secara aman tanpa crash
     */
    private function removeKeaLeaseFile(string $filePath): void
    {
        if (!File::exists($filePath)) return;

        try {
            @chmod($filePath, 0666);
            File::delete($filePath);
            return;
        } catch (\Throwable $e) {
            @unlink($filePath);
        }

        // Fallback: Hapus melalui container radius_dhcp
        try {
            $filename = basename($filePath);
            shell_exec("docker exec radius_dhcp rm -f /var/lib/kea/{$filename} 2>/dev/null");
        } catch (\Throwable $e) {}
    }

    /**
     * Panggil Docker Engine API via Unix Socket /var/run/docker.sock
     */
    private function callDockerSocketApi(string $path, string $method = 'POST'): bool
    {
        $sock = '/var/run/docker.sock';
        if (!file_exists($sock) && @filetype($sock) !== 'socket') {
            return false;
        }

        try {
            $ch = curl_init("http://localhost" . $path);
            curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $sock);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Docker API mengembalikan 204 No Content untuk stop/start/restart sukses
            if ($httpCode >= 200 && $httpCode < 300) {
                Log::info("Docker API {$method} {$path} sukses (HTTP {$httpCode}).");
                return true;
            } else {
                Log::warning("Docker API {$method} {$path} response code {$httpCode}: {$response}");
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal call Docker API via socket: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Stop container radius_dhcp
     */
    public function stopKeaService(): void
    {
        // 1. Coba via Docker Engine Socket API
        if ($this->callDockerSocketApi('/containers/radius_dhcp/stop?t=10', 'POST')) {
            return;
        }

        // 2. Fallback CLI
        try {
            if (trim(shell_exec('which docker 2>/dev/null') ?? '')) {
                shell_exec('docker stop --time=10 radius_dhcp 2>/dev/null');
            } elseif (trim(shell_exec('which systemctl 2>/dev/null') ?? '')) {
                shell_exec('sudo systemctl stop kea-dhcp4-server 2>/dev/null');
            } else {
                shell_exec('pkill kea-dhcp4 2>/dev/null');
                sleep(2);
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal stop Kea service: " . $e->getMessage());
        }
    }

    /**
     * Start container radius_dhcp
     */
    public function startKeaService(): void
    {
        // 1. Coba via Docker Engine Socket API
        if ($this->callDockerSocketApi('/containers/radius_dhcp/start', 'POST')) {
            return;
        }

        // 2. Fallback CLI
        try {
            if (trim(shell_exec('which docker 2>/dev/null') ?? '')) {
                shell_exec('docker start radius_dhcp 2>/dev/null');
            } elseif (trim(shell_exec('which systemctl 2>/dev/null') ?? '')) {
                shell_exec('sudo systemctl start kea-dhcp4-server 2>/dev/null');
            } else {
                shell_exec('kea-dhcp4 -c /etc/kea/kea-dhcp4.conf &');
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal start Kea service: " . $e->getMessage());
        }
    }

    /**
     * Restart container radius_dhcp / service Kea DHCP
     */
    public function restartKeaService(): void
    {
        // 1. Coba via Docker Engine Socket API
        if ($this->callDockerSocketApi('/containers/radius_dhcp/restart?t=5', 'POST')) {
            return;
        }

        // 2. Fallback CLI
        try {
            if (trim(shell_exec('which docker 2>/dev/null') ?? '')) {
                shell_exec('docker restart radius_dhcp 2>/dev/null');
            } elseif (trim(shell_exec('which systemctl 2>/dev/null') ?? '')) {
                shell_exec('sudo systemctl restart kea-dhcp4-server 2>/dev/null');
            } else {
                shell_exec('pkill -HUP kea-dhcp4 2>/dev/null');
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal restart Kea service: " . $e->getMessage());
        }
    }

    /**
     * Kirim perintah ke Kea via Control Socket — untuk lease4-wipe, lease4-del, dsb.
     * Menggunakan direct Unix domain socket via stream_socket_client, dengan fallback docker exec socat.
     *
     * @param  array  $command  Misal: ['command' => 'lease4-wipe', 'arguments' => ['subnet-id' => 0]]
     * @return string|null Response JSON dari Kea, atau null jika gagal
     */
    public function sendKeaControlCommand(array $command): ?string
    {
        $json = json_encode($command);

        // Metode 1: Direct stream_socket_client ke socket yang di-share (/var/run/kea/kea4-ctrl-socket)
        $socketPaths = [
            '/var/run/kea/kea4-ctrl-socket',
            storage_path('dhcp/leases/kea4-ctrl-socket'),
        ];

        foreach ($socketPaths as $sock) {
            if (file_exists($sock) || @filetype($sock) === 'socket') {
                try {
                    $client = @stream_socket_client("unix://{$sock}", $errno, $errstr, 2);
                    if ($client) {
                        fwrite($client, $json);
                        $response = '';
                        while (!feof($client)) {
                            $chunk = fread($client, 8192);
                            if ($chunk === false) break;
                            $response .= $chunk;
                        }
                        fclose($client);
                        if (!empty($response)) {
                            return $response;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::debug("Direct socket {$sock} connect error: " . $e->getMessage());
                }
            }
        }

        // Metode 2: Fallback eksekusi socat di container Kea (jika container running & docker exec tersedia)
        try {
            $escaped = escapeshellarg($json);
            $result  = shell_exec(
                "docker exec radius_dhcp sh -c "
                . escapeshellarg(
                    "echo {$escaped} | socat - UNIX-CONNECT:/var/run/kea/kea4-ctrl-socket 2>/dev/null"
                )
                . " 2>/dev/null"
            );
            return $result ?: null;
        } catch (\Throwable $e) {
            Log::warning("sendKeaControlCommand fallback gagal: " . $e->getMessage());
            return null;
        }
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
