<?php

namespace App\Services;

use App\Models\UnifiConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UniFiService
{
    protected ?UnifiConfig $config = null;
    protected ?string $cookie = null;

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
                'username'        => env('UNIFI_USERNAME', 'admin'),
                'password'        => env('UNIFI_PASSWORD', 'password'),
                'verify_ssl'      => false,
            ]);
        }
        return $this->config;
    }

    /**
     * Authenticate with UniFi Controller API and return session cookie
     */
    protected function login(): bool
    {
        $config = $this->getConfig();
        $url = rtrim($config->controller_url, '/') . '/api/login';

        try {
            $response = Http::withOptions([
                'verify' => (bool)$config->verify_ssl,
                'timeout' => 10,
            ])->post($url, [
                'username' => $config->username,
                'password' => $config->password,
            ]);

            if ($response->successful()) {
                $cookies = $response->cookies();
                foreach ($cookies as $c) {
                    if ($c->getName() === 'unifises') {
                        $this->cookie = 'unifises=' . $c->getValue();
                        return true;
                    }
                }
                // Fallback extract Set-Cookie header
                $headerCookie = $response->header('Set-Cookie');
                if ($headerCookie) {
                    $this->cookie = $headerCookie;
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::error('UniFi Controller Login Failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Generate Vouchers on UniFi Controller & Return Voucher Data
     * 
     * @param int $count Number of vouchers to generate
     * @param int $durationMinutes Duration in minutes
     * @param int $useLimit Allowed uses per voucher (1 = single use)
     * @param int|null $quotaMB Quota limit in MB
     * @param int|null $downKbps Download speed limit in Kbps
     * @param int|null $upKbps Upload speed limit in Kbps
     * @param string|null $note Custom note/tag
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
        
        // Prepare API payload according to UniFi Hotspot API specs
        $payload = [
            'cmd'       => 'create-voucher',
            'n'         => $count,
            'expire'    => $durationMinutes,
            'quota'     => $useLimit === 1 ? 0 : $useLimit, // 0 = multi-use off or single use
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

        // Try API request to UniFi Controller if login succeeds
        if ($this->login()) {
            $url = rtrim($config->controller_url, '/') . '/api/s/' . $config->site_id . '/cmd/hotspot';
            try {
                $response = Http::withHeaders([
                    'Cookie' => $this->cookie,
                ])->withOptions([
                    'verify' => (bool)$config->verify_ssl,
                    'timeout' => 15,
                ])->post($url, $payload);

                if ($response->successful() && isset($response['data'])) {
                    return [
                        'success' => true,
                        'data'    => $response['data'],
                        'mode'    => 'unifi_api',
                    ];
                }
            } catch (\Exception $e) {
                Log::error('UniFi Voucher API Error: ' . $e->getMessage());
            }
        }

        // Fallback: Generate Vouchers locally if Controller is offline/unreachable
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
}
