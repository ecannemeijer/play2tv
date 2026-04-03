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
        $limits = (new SecurityThrottleService())->enforce($request);

        if ($limits !== null) {
            $retryAfter = max(1, (int) ($limits['retry_after'] ?? 60));

            return response()
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
}
