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
     * Alias untuk syncConfigFile()
     */
    public function syncConfig(): bool
    {
        return $this->syncConfigFile();
    }

    /**
     * Memicu reload Kea DHCP server tanpa restart container (zero-downtime)
     */
    public function reloadKeaService(): void
    {
        try {
            // 1. Coba reload via Kea control-socket (zero-downtime)
            $res = $this->sendKeaControlCommand(['command' => 'config-reload']);

            // 2. Jika socket tidak merespon atau config-reload tidak tersedia, restart container Kea
            if (empty($res) || strpos($res, '"result": 0') === false) {
                if (shell_exec('which docker')) {
                    shell_exec('docker restart radius_dhcp 2>/dev/null');
                }
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

            // Hentikan Kea agar memori ter-flush ke disk
            $this->stopKeaService();
            sleep(3);

            // Tulis ulang file utama dengan header kosong saja
            $this->writeKeaLeasesFile($defaultHeader);

            // Hapus SEMUA file LFC Kea agar tidak di-restore saat startup:
            // .1   = LFC output file (Kea pakai ini untuk overwrite .csv saat startup!)
            // .2   = copy lama file utama saat LFC mulai
            // .completed = hasil LFC final
            // .bak = backup lama
            foreach (['.1', '.2', '.completed', '.bak'] as $ext) {
                $this->removeKeaLeaseFile($this->leasesPath . $ext);
            }

            // Jalankan kembali Kea
            $this->startKeaService();

            return $clearedCount;
        }

        // =====================================================================
        // KASUS B: Bersihkan lease per subnet tertentu
        // =====================================================================
        $subnet = DhcpSubnet::find($subnetId);
        if (!$subnet) {
            Log::warning("clearLeases: Subnet ID {$subnetId} tidak ditemukan di database.");
            return 0;
        }

        // 1. HENTIKAN Kea DHCP DULU agar seluruh lease di memori (RAM) ter-flush sempurna ke disk!
        Log::info("clearLeases: Menghentikan Kea DHCP sebelum membaca dan memodifikasi file leases...");
        $this->stopKeaService();
        // stopKeaService sudah polling sampai container benar-benar berhenti.
        // Tambahan 1 detik ekstra untuk memastikan semua write ke disk selesai (fsync).
        sleep(1);

        // Hapus file .1 (LFC interim output) SEBELUM membaca file lain,
        // agar tidak ada kemungkinan race-condition saat file .1 sedang ditulis Kea saat shutdown.
        // File .1 ini jika dibiarkan akan di-rename menjadi .csv oleh Kea saat startup berikutnya!
        $this->removeKeaLeaseFile($this->leasesPath . '.1');

        // 2. Kumpulkan baris dari file terbaru ke terlama (.csv utama, .2, lalu .completed)
        $potentialFiles = [
            $this->leasesPath,
            $this->leasesPath . '.2',
            $this->leasesPath . '.completed',
        ];

        $headerLine         = null;
        $preservedLinesByIp = [];
        $clearedIps         = [];
        $clearedCount       = 0;

        foreach ($potentialFiles as $filePath) {
            if (!file_exists($filePath)) {
                Log::info("clearLeases: File tidak ditemukan (skip): " . basename($filePath));
                continue;
            }

            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lineCount = count($lines);
            Log::info("clearLeases: Membaca " . basename($filePath) . " — {$lineCount} baris (termasuk header).");

            if (empty($lines) || $lineCount <= 1) {
                Log::info("clearLeases: " . basename($filePath) . " kosong atau hanya header, skip.");
                continue;
            }

            $currentHeader = array_shift($lines);
            if (!$headerLine && !empty($currentHeader)) {
                $headerLine = $currentHeader;
            }

            $headers = str_getcsv($currentHeader);
            $addrIdx = array_search('address', array_map('trim', $headers));
            $subIdx  = array_search('subnet_id', array_map('trim', $headers));

            foreach ($lines as $rawLine) {
                $cols = str_getcsv($rawLine);
                if (empty($cols) || count($cols) < 3) {
                    continue;
                }

                $ip       = ($addrIdx !== false && isset($cols[$addrIdx])) ? trim($cols[$addrIdx]) : '';
                $rowSubId = ($subIdx !== false && isset($cols[$subIdx])) ? (int)trim($cols[$subIdx]) : 0;

                if (empty($ip)) {
                    continue;
                }

                // Cek apakah lease ini milik subnet yang ingin dibersihkan.
                //
                // ATURAN PENTING:
                //   - Jika subnet_id di CSV valid (> 0), PERCAYA PENUH pada subnet_id.
                //     Jangan gunakan containsIp() karena CIDR subnet bisa overlap dengan subnet lain
                //     (misal: subnet /16 mencakup seluruh range termasuk subnet /24 di dalamnya).
                //   - containsIp() hanya dipakai sebagai fallback untuk baris dengan subnet_id = 0
                //     (lease lama yang tidak memiliki subnet_id tercatat).
                $matches = false;
                if ($rowSubId > 0) {
                    // Ada subnet_id valid → match hanya berdasarkan subnet_id, TIDAK cek CIDR
                    $matches = ($rowSubId === (int)$subnet->id);
                    if (!$matches) {
                        // subnet_id berbeda → ini pasti milik subnet lain, jangan hapus
                        Log::debug("clearLeases: SKIP IP {$ip} (subnet_id={$rowSubId} bukan target {$subnet->id}) dari " . basename($filePath));
                    }
                } else {
                    // subnet_id = 0 atau tidak ada → fallback ke cek CIDR/pool range
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        $matches = $subnet->containsIp($ip);
                        if ($matches) {
                            Log::info("clearLeases: HAPUS via CIDR-fallback IP {$ip} (subnet_id=0) dari " . basename($filePath));
                        }
                    }
                }

                if ($matches) {
                    // Hanya hitung jika IP belum pernah ditemukan sebelumnya (hindari double count)
                    if (!isset($clearedIps[$ip])) {
                        $clearedCount++;
                        Log::info("clearLeases: HAPUS lease IP {$ip} (subnet_id={$rowSubId}) dari " . basename($filePath));
                    }
                    $clearedIps[$ip] = true;
                    // Pastikan dihapus dari daftar preservasi (bahkan jika ada catatan lama di file .completed/.2)
                    unset($preservedLinesByIp[$ip]);
                } else {
                    // Hanya pertahankan jika IP ini TIDAK pernah ditandai untuk dihapus
                    if (!isset($clearedIps[$ip])) {
                        $preservedLinesByIp[$ip] = $rawLine;
                    }
                }
            }
        }

        Log::info("clearLeases: Total dihapus={$clearedCount}, dipertahankan=" . count($preservedLinesByIp) . " lease untuk subnet {$subnet->name} (ID: {$subnetId}).");

        if (!$headerLine) {
            $headerLine = $defaultHeader;
        }

        $preservedLines = array_values($preservedLinesByIp);

        // 3. Tulis data baru: header asli + baris lease subnet lain yang dipertahankan
        $newContent = $headerLine . "\n";
        if (!empty($preservedLines)) {
            $newContent .= implode("\n", $preservedLines) . "\n";
        }

        // Tulis ulang file leases CSV utama
        $this->writeKeaLeasesFile($newContent);

        // Hapus SEMUA file LFC Kea (.1, .2, .completed, .bak) agar Kea tidak me-restore dari backup LFC lama
        foreach (['.1', '.2', '.completed', '.bak'] as $ext) {
            $this->removeKeaLeaseFile($this->leasesPath . $ext);
        }

        // 4. NYALAKAN KEMBALI container radius_dhcp
        Log::info("clearLeases: Menyalakan kembali Kea DHCP setelah mengosongkan {$clearedCount} dynamic leases untuk Subnet {$subnet->name} (ID: {$subnetId}).");
        $this->startKeaService();

        return $clearedCount;
    }

    /**
     * Jalankan perintah sebagai root di dalam container radius_laravel_app via Docker API exec.
     * radius_laravel_app SELALU running sehingga bisa dipakai bahkan setelah radius_dhcp di-stop.
     *
     * @param  array  $cmd  Misal: ['chmod', '777', '/var/www/html/storage/dhcp/leases/kea-leases4.csv']
     */
    private function dockerExecAsRoot(array $cmd): bool
    {
        $sock = '/var/run/docker.sock';
        if (!file_exists($sock)) {
            return false;
        }

        $body = json_encode([
            'User'         => 'root',
            'AttachStdout' => true,
            'AttachStderr' => true,
            'Cmd'          => $cmd,
        ]);

        // Step 1: Buat exec instance
        $createResult = shell_exec(
            "curl -s -X POST "
            . "-H 'Content-Type: application/json' "
            . "-d " . escapeshellarg($body) . " "
            . "--unix-socket {$sock} "
            . "'http://localhost/containers/radius_laravel_app/exec' 2>/dev/null"
        );
        $data   = json_decode($createResult ?? '', true);
        $execId = $data['Id'] ?? null;

        if (!$execId) {
            Log::warning("dockerExecAsRoot: Gagal buat exec instance. Response: " . substr($createResult ?? '', 0, 200));
            return false;
        }

        // Step 2: Jalankan exec (Detach=true agar tidak blocking)
        shell_exec(
            "curl -s -X POST "
            . "-H 'Content-Type: application/json' "
            . "-d '{\"Detach\":true,\"Tty\":false}' "
            . "--unix-socket {$sock} "
            . escapeshellarg("http://localhost/exec/{$execId}/start") . " 2>/dev/null"
        );

        usleep(800000); // 800ms cukup untuk chmod/write selesai
        return true;
    }

    /**
     * Tulis ke file kea-leases4.csv secara aman.
     *
     * Metode 1: File::put langsung (jika file masih milik www-data)
     * Metode 2: chmod via Docker API exec (root di radius_laravel_app) → File::put
     * Metode 3: Tulis konten via Docker API exec (root) langsung ke file
     */
    private function writeKeaLeasesFile(string $content): void
    {
        $bytes        = strlen($content);
        $containerDir = '/var/www/html/storage/dhcp/leases';
        $filename     = basename($this->leasesPath);
        $containerPath = "{$containerDir}/{$filename}";

        // Metode 1: Direct File::put
        try {
            @chmod($this->leasesPath, 0666);
            File::put($this->leasesPath, $content);
            @chmod($this->leasesPath, 0666);
            Log::info("writeKeaLeasesFile: OK via File::put ({$bytes} bytes).");
            return;
        } catch (\Throwable $e) {
            Log::warning("writeKeaLeasesFile: File::put gagal ({$bytes} bytes): " . $e->getMessage());
        }

        // Metode 2: chmod via Docker exec root → lalu File::put
        Log::info("writeKeaLeasesFile: Mencoba chmod via Docker exec root...");
        $this->dockerExecAsRoot(['chmod', '777', $containerPath]);

        try {
            File::put($this->leasesPath, $content);
            @chmod($this->leasesPath, 0666);
            Log::info("writeKeaLeasesFile: OK via chmod-root + File::put ({$bytes} bytes).");
            return;
        } catch (\Throwable $e) {
            Log::warning("writeKeaLeasesFile: Setelah chmod-root, File::put masih gagal: " . $e->getMessage());
        }

        // Metode 3: Tulis langsung via Docker exec root (base64 untuk menghindari masalah escaping)
        Log::info("writeKeaLeasesFile: Mencoba tulis via Docker exec root langsung...");
        $b64     = base64_encode($content);
        $writeCmd = "echo " . escapeshellarg($b64) . " | base64 -d > {$containerPath} && chmod 666 {$containerPath}";
        if ($this->dockerExecAsRoot(['sh', '-c', $writeCmd])) {
            Log::info("writeKeaLeasesFile: OK via Docker exec root write ({$bytes} bytes).");
            return;
        }

        Log::error("writeKeaLeasesFile: SEMUA METODE GAGAL untuk {$bytes} bytes!");
    }

    /**
     * Hapus file LFC Kea (.1 .2 .completed .bak) secara aman
     */
    private function removeKeaLeaseFile(string $filePath): void
    {
        if (!file_exists($filePath)) return;

        // Coba hapus langsung (jika file milik www-data)
        @chmod($filePath, 0666);
        if (@unlink($filePath)) {
            return;
        }

        // Fallback: chmod dulu via Docker exec root, lalu hapus
        $containerPath = '/var/www/html/storage/dhcp/leases/' . basename($filePath);
        $this->dockerExecAsRoot(['rm', '-f', $containerPath]);
        Log::info("removeKeaLeaseFile: Hapus " . basename($filePath) . " via Docker exec root.");
    }


    /**
     * Panggil Docker Engine API via Unix Socket /var/run/docker.sock
     * Metode 1: shell_exec curl --unix-socket (paling reliable di Alpine container)
     * Metode 2: PHP curl CURLOPT_UNIX_SOCKET_PATH (fallback)
     */
    private function callDockerSocketApi(string $path, string $method = 'POST'): bool
    {
        $sock = '/var/run/docker.sock';
        if (!file_exists($sock)) {
            Log::warning("Docker socket tidak ditemukan: {$sock}");
            return false;
        }

        // Metode 1: Shell exec curl via unix socket (system curl lebih reliable)
        try {
            $m = strtoupper($method);
            $cmd = "curl -s -o /dev/null -w \"%{http_code}\" -X {$m} --unix-socket {$sock} "
                 . escapeshellarg("http://localhost{$path}") . " 2>/dev/null";
            $httpCode = (int) trim(shell_exec($cmd) ?? '0');

            if ($httpCode >= 200 && $httpCode < 300) {
                Log::info("Docker API curl-shell {$method} {$path} sukses (HTTP {$httpCode}).");
                return true;
            }
            // 304 = container sudah dalam state yang diinginkan (misal: start pada container running)
            if ($httpCode === 304) {
                Log::info("Docker API {$method} {$path}: container sudah dalam state yang diinginkan (HTTP 304).");
                return true;
            }
            if ($httpCode > 0) {
                Log::warning("Docker API curl-shell {$method} {$path} response {$httpCode}");
            }
        } catch (\Throwable $e) {
            Log::warning("Docker API curl-shell gagal: " . $e->getMessage());
        }

        // Metode 2: PHP curl dengan CURLOPT_UNIX_SOCKET_PATH (fallback)
        try {
            $ch = curl_init("http://localhost" . $path);
            curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $sock);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                Log::info("Docker API PHP-curl {$method} {$path} sukses (HTTP {$httpCode}).");
                return true;
            }
            if ($httpCode === 304) {
                return true;
            }
            if ($httpCode > 0) {
                Log::warning("Docker API PHP-curl {$method} {$path} response code {$httpCode}: {$response}");
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal call Docker API via PHP curl: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Stop container radius_dhcp
     */
    public function stopKeaService(): void
    {
        // 1. Coba via Docker Engine Socket API
        $this->callDockerSocketApi('/containers/radius_dhcp/stop?t=10', 'POST');

        // 2. Fallback CLI jika socket gagal atau container belum berhenti
        try {
            if (trim(shell_exec('which docker 2>/dev/null') ?? '')) {
                shell_exec('docker stop --time=10 radius_dhcp 2>/dev/null');
            } elseif (trim(shell_exec('which systemctl 2>/dev/null') ?? '')) {
                shell_exec('sudo systemctl stop kea-dhcp4-server 2>/dev/null');
            } else {
                shell_exec('pkill kea-dhcp4 2>/dev/null');
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal stop Kea service: " . $e->getMessage());
        }

        // 3. Polling: tunggu sampai container benar-benar berhenti (max 15 detik)
        $maxWait = 15;
        $waited  = 0;
        while ($waited < $maxWait) {
            sleep(1);
            $waited++;
            try {
                $state = trim(shell_exec(
                    "docker inspect --format='{{.State.Running}}' radius_dhcp 2>/dev/null"
                ) ?? '');
                if ($state === 'false' || $state === '') {
                    Log::info("stopKeaService: container radius_dhcp confirmed stopped after {$waited}s.");
                    break;
                }
            } catch (\Throwable $e) {
                break; // jika docker tidak ada, skip polling
            }
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
