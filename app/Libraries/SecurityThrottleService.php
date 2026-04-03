<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\HTTP\RequestInterface;
use Config\Services;

class SecurityThrottleService
{
    private CacheInterface $cache;
    private JwtLibrary $jwt;
    private SecurityEventService $events;

    public function __construct()
    {
        $this->cache  = Services::cache();
        $this->jwt    = new JwtLibrary();
        $this->events = new SecurityEventService();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function enforce(RequestInterface $request): ?array
    {
        $path      = trim($request->getUri()->getPath(), '/');
        $method    = strtoupper($request->getMethod());
        $ipAddress = $request->getIPAddress();
        $throttler = Services::throttler();

        if ($path === 'api/login' && $method === 'POST') {
            $body       = $this->extractJsonBody($request);
            $identifier = strtolower(trim((string) ($body['email'] ?? 'anonymous')));
            $retryAfter = $this->getLoginRetryAfter($identifier, $ipAddress);

            if ($retryAfter > 0) {
                return [
                    'message'     => 'Te veel inlogpogingen. Wacht voordat je opnieuw probeert.',
                    'retry_after' => $retryAfter,
                ];
            }

            $maxAttempts = (int) env('rateLimit.loginMaxAttempts', 5);
            $window      = (int) env('rateLimit.loginWindowSeconds', 300);
            if (! $throttler->check('login_ip_' . sha1($ipAddress), $maxAttempts, $window)) {
                return [
                    'message'     => 'Te veel inlogpogingen vanaf dit IP-adres.',
                    'retry_after' => max(1, $throttler->getTokenTime()),
                ];
            }

            return null;
        }

        if (! str_starts_with($path, 'api/')) {
            return null;
        }

        $ipLimit   = (int) env('rateLimit.apiIpMaxAttempts', 300);
        $ipWindow  = (int) env('rateLimit.apiIpWindowSeconds', 60);
        if (! $throttler->check('api_ip_' . sha1($ipAddress), $ipLimit, $ipWindow)) {
            $this->events->log('api_rate_limit_ip', 'warning', $request instanceof \CodeIgniter\HTTP\IncomingRequest ? $request : null, null, [
                'ip' => $ipAddress,
                'path' => $path,
            ]);

            return [
                'message'     => 'Te veel verzoeken vanaf dit IP-adres.',
                'retry_after' => max(1, $throttler->getTokenTime()),
            ];
        }

        $userId = $this->extractUserId($request->getHeaderLine('Authorization'));
        if ($userId !== null) {
            $userLimit  = (int) env('rateLimit.apiUserMaxAttempts', 180);
            $userWindow = (int) env('rateLimit.apiUserWindowSeconds', 60);

            if (! $throttler->check('api_user_' . $userId, $userLimit, $userWindow)) {
                $this->events->log('api_rate_limit_user', 'warning', $request instanceof \CodeIgniter\HTTP\IncomingRequest ? $request : null, $userId, [
                    'ip' => $ipAddress,
                    'path' => $path,
                ]);

                return [
                    'message'     => 'Te veel verzoeken voor deze gebruiker.',
                    'retry_after' => max(1, $throttler->getTokenTime()),
                ];
            }
        }

        return null;
    }

    public function recordLoginFailure(string $email, string $ipAddress): int
    {
        $key      = $this->loginFailureKey($email, $ipAddress);
        $attempts = (int) $this->cache->get($key);
        $attempts++;

        $this->cache->save($key, $attempts, 3600);

        $backoff = $attempts <= 1 ? 0 : min(300, (int) pow(2, min($attempts - 1, 8)));
        if ($backoff > 0) {
            $this->cache->save($key . '_retry_until', time() + $backoff, $backoff);
        }

        return $backoff;
    }

    public function clearLoginFailures(string $email, string $ipAddress): void
    {
        $key = $this->loginFailureKey($email, $ipAddress);
        $this->cache->delete($key);
        $this->cache->delete($key . '_retry_until');
    }

    public function getLoginRetryAfter(string $email, string $ipAddress): int
    {
        $retryUntil = (int) $this->cache->get($this->loginFailureKey($email, $ipAddress) . '_retry_until');

        return max(0, $retryUntil - time());
    }

    /**
     * @return array<string, mixed>
     */
    private function extractJsonBody(RequestInterface $request): array
    {
        $raw = trim((string) $request->getBody());
        if ($raw === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function extractUserId(string $authorizationHeader): ?int
    {
        if (! str_starts_with($authorizationHeader, 'Bearer ')) {
            return null;
        }

        try {
            $payload = $this->jwt->decode(substr($authorizationHeader, 7));

            return isset($payload->sub) ? (int) $payload->sub : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function loginFailureKey(string $email, string $ipAddress): string
    {
        return 'login_fail_' . sha1(strtolower(trim($email)) . '|' . $ipAddress);
    }
}