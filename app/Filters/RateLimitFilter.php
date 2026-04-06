<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\SecurityThrottleService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RateLimitFilter
 *
 * Applies rate limiting to the login endpoint.
 * Blocks excessive login attempts from the same IP using CI4's throttler.
 *
 * Config:
 *   rateLimit.loginMaxAttempts  = 5  (from .env)
 *   rateLimit.loginWindowSeconds = 300
 *
 * Returns HTTP 429 when limit is exceeded.
 */
class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return null;
        }

        $limits = (new SecurityThrottleService())->enforce($request);

        if ($limits !== null) {
            $retryAfter = max(1, (int) ($limits['retry_after'] ?? 60));

            return $this->withCorsHeaders(response())
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $retryAfter)
                ->setContentType('application/json')
                ->setJSON([
                    'success'     => false,
                    'message'     => (string) ($limits['message'] ?? 'Te veel verzoeken.'),
                    'retry_after' => $retryAfter,
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function withCorsHeaders(ResponseInterface $response): ResponseInterface
    {
        $origin = $this->resolveAllowedOrigin(request()->getHeaderLine('Origin'));

        $response
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Api-Key, X-Timestamp, X-Signature, X-Device-Id');

        if ($origin !== null) {
            $response
                ->setHeader('Access-Control-Allow-Origin', $origin)
                ->setHeader('Access-Control-Allow-Credentials', 'true')
                ->appendHeader('Vary', 'Origin');
        }

        return $response;
    }

    private function resolveAllowedOrigin(string $origin): ?string
    {
        if ($origin === '') {
            return null;
        }

        foreach ($this->getAllowedOrigins() as $allowedOrigin) {
            if (hash_equals($allowedOrigin, $origin)) {
                return $origin;
            }
        }

        return null;
    }

    private function getAllowedOrigins(): array
    {
        $configured = trim((string) env('cors.allowedOrigins', 'https://app.play2tv.nl,https://dashboard.play2tv.nl,https://user.velixatv.com'));

        return array_values(array_filter(array_map('trim', explode(',', $configured))));
    }
}
