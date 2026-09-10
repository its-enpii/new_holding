<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TenantApplication;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Checks a subsidiary instance using GET {instance_url}/api/v1/holding/ping with
 * Authorization: Bearer {api_secret}. A subsidiary must respond 200 with JSON
 * {"status":"success"} to be considered connected; 401/403 means the URL or secret
 * is wrong, while connection failures, other responses, and 5xx mean offline.
 */
final class AppConnectionCheckService
{
    /**
     * @return array{status: 'connected'|'auth_error'|'offline', latency_ms: int, checked_at: string, message: string}
     */
    public function check(TenantApplication $tenantApplication): array
    {
        $startedAt = microtime(true);

        try {
            $response = Http::baseUrl(rtrim($tenantApplication->instance_url, '/'))
                ->acceptJson()
                ->timeout(5)
                ->withToken($tenantApplication->api_secret)
                ->get('/api/v1/holding/ping');
        } catch (ConnectionException) {
            return $this->result('offline', $startedAt, 'Instance tidak dapat dihubungi.');
        }

        if (in_array($response->status(), [401, 403], true)) {
            return $this->result('auth_error', $startedAt, 'Secret atau URL instance salah.');
        }

        if ($response->status() === 200 && $response->json('status') === 'success') {
            return $this->result('connected', $startedAt, 'Koneksi berhasil.');
        }

        return $this->result('offline', $startedAt, 'Instance tidak dapat dihubungi.');
    }

    /**
     * @return array{status: 'connected'|'auth_error'|'offline', latency_ms: int, checked_at: string, message: string}
     */
    private function result(string $status, float $startedAt, string $message): array
    {
        return [
            'status' => $status,
            'latency_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            'checked_at' => now()->toISOString(),
            'message' => $message,
        ];
    }
}
