<?php

namespace App\Services;

use App\Models\UnifiConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UniFiService
{
    protected ?UnifiConfig $config = null;
    protected ?string $cookie = null;
    protected ?string $csrfToken = null;
    protected bool $isUnifiOs = false;

    public function __construct()
    {
        $this->config = UnifiConfig::first();
    }

    /**
     * Get or create active UniFi config
     */
    public function getConfig(): UnifiConfig
    {
        if (!$this->config) {
            $this->config = UnifiConfig::create([
                'controller_url' => env('UNIFI_CONTROLLER_URL', 'https://127.0.0.1:8443'),
                'site_id'        => env('UNIFI_SITE_ID', 'default'),
                'username'       => env('UNIFI_USERNAME', 'admin'),
                'password'       => env('UNIFI_PASSWORD', 'password'),
                'verify_ssl'     => false,
            ]);
        }
        return $this->config;
    }

    /**
     * Authenticate with UniFi Controller API (supports both UDM UniFi OS & Legacy Controllers)
     */
    protected function login(): bool
    {
        $config = $this->getConfig();
        $baseUrl = rtrim($config->controller_url, '/');

        // 1. Try UDM UniFi OS login endpoint (/api/auth/login)
        try {
            $response = Http::withOptions([
                'verify'  => (bool)$config->verify_ssl,
                'timeout' => 10,
            ])->post($baseUrl . '/api/auth/login', [
                'username' => $config->username,
                'password' => $config->password,
            ]);

            if ($response->successful()) {
                $this->isUnifiOs = true;
                $this->extractCookiesAndHeaders($response);
                return true;
            }
        } catch (\Exception $e) {
            Log::warning('UniFi OS Login (/api/auth/login) failed, attempting legacy endpoint: ' . $e->getMessage());
        }

        // 2. Fallback to Legacy Controller login endpoint (/api/login)
        try {
            $response = Http::withOptions([
                'verify'  => (bool)$config->verify_ssl,
                'timeout' => 10,
            ])->post($baseUrl . '/api/login', [
                'username' => $config->username,
                'password' => $config->password,
            ]);

            if ($response->successful()) {
                $this->isUnifiOs = false;
                $this->extractCookiesAndHeaders($response);
                return true;
            }
        } catch (\Exception $e) {
            Log::error('UniFi Controller Login Failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Extract session cookies and CSRF token from HTTP response
     */
    protected function extractCookiesAndHeaders($response): void
    {
        $cookieArray = [];
        $cookies = $response->cookies();
        foreach ($cookies as $c) {
            $cookieArray[] = $c->getName() . '=' . $c->getValue();
        }

        if (!empty($cookieArray)) {
            $this->cookie = implode('; ', $cookieArray);
        } else {
            $this->cookie = $response->header('Set-Cookie');
        }

        $this->csrfToken = $response->header('X-CSRF-Token') ?: $response->header('x-csrf-token');
    }

    /**
     * Generate Vouchers on UniFi Controller & Return Voucher Data
     */
    public function createVouchers(
        int $count = 1,
        int $durationMinutes = 1440,
        int $useLimit = 1,
        ?int $quotaMB = null,
        ?int $downKbps = null,
        ?int $upKbps = null,
        ?string $note = null
    ): array {
        $config = $this->getConfig();
        
        $payload = [
            'cmd'    => 'create-voucher',
            'n'      => $count,
            'expire' => $durationMinutes,
            'quota'  => $useLimit === 1 ? 0 : $useLimit,
        ];

        if ($note) {
            $payload['note'] = $note;
        }
        if ($quotaMB) {
            $payload['bytes'] = $quotaMB;
        }
        if ($downKbps) {
            $payload['down'] = $downKbps;
        }
        if ($upKbps) {
            $payload['up'] = $upKbps;
        }

        if ($this->login()) {
            $baseUrl = rtrim($config->controller_url, '/');
            
            // Endpoint for UniFi OS UDM is /proxy/network/api/s/{site}/cmd/hotspot, legacy is /api/s/{site}/cmd/hotspot
            $endpointPath = $this->isUnifiOs
                ? '/proxy/network/api/s/' . $config->site_id . '/cmd/hotspot'
                : '/api/s/' . $config->site_id . '/cmd/hotspot';

            $url = $baseUrl . $endpointPath;

            $headers = ['Cookie' => $this->cookie];
            if ($this->csrfToken) {
                $headers['X-CSRF-Token'] = $this->csrfToken;
            }

            try {
                $response = Http::withHeaders($headers)
                    ->withOptions(['verify' => (bool)$config->verify_ssl, 'timeout' => 15])
                    ->post($url, $payload);

                if ($response->successful() && isset($response['data'])) {
                    // Query created vouchers back from controller to get actual voucher codes
                    $vouchersData = $this->fetchCreatedVouchers($baseUrl, $config->site_id, $headers, $count);
                    
                    return [
                        'success' => true,
                        'data'    => !empty($vouchersData) ? $vouchersData : $response['data'],
                        'mode'    => 'unifi_api',
                    ];
                } else {
                    Log::error('UniFi Voucher Creation Failed HTTP Status ' . $response->status() . ': ' . $response->body());
                }
            } catch (\Exception $e) {
                Log::error('UniFi Voucher API Exception: ' . $e->getMessage());
            }
        }

        // Fallback: Generate Vouchers locally if Controller API is unreachable
        $generated = [];
        $batchId = 'batch_' . time() . '_' . rand(100, 999);
        for ($i = 0; $i < $count; $i++) {
            $code = sprintf('%05d%05d', rand(10000, 99999), rand(10000, 99999));
            $generated[] = [
                'code'             => $code,
                'duration_minutes' => $durationMinutes,
                'quota_mb'         => $quotaMB,
                'down_kbps'        => $downKbps,
                'up_kbps'          => $upKbps,
                'use_limit'        => $useLimit,
                'note'             => $note ?: 'Local Voucher Generator',
                'batch_id'         => $batchId,
                'create_time'      => time(),
            ];
        }

        return [
            'success' => true,
            'data'    => $generated,
            'mode'    => 'local_fallback',
        ];
    }

    /**
     * Fetch newly generated vouchers from Controller to get real voucher codes
     */
    protected function fetchCreatedVouchers(string $baseUrl, string $siteId, array $headers, int $limit = 10): array
    {
        $endpointPath = $this->isUnifiOs
            ? '/proxy/network/api/s/' . $siteId . '/stat/voucher'
            : '/api/s/' . $siteId . '/stat/voucher';

        try {
            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => false, 'timeout' => 10])
                ->get($baseUrl . $endpointPath);

            if ($response->successful() && isset($response['data']) && is_array($response['data'])) {
                // Return latest N vouchers sorted by create_time desc
                $all = $response['data'];
                usort($all, fn($a, $b) => ($b['create_time'] ?? 0) <=> ($a['create_time'] ?? 0));
                return array_slice($all, 0, $limit);
            }
        } catch (\Exception $e) {
            Log::error('Fetch UniFi Vouchers Stat Error: ' . $e->getMessage());
        }

        return [];
    }
}
