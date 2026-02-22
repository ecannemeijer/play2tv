<?php

declare(strict_types=1);

namespace App\Filters;

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
        $throttler = \Config\Services::throttler();
        $ip        = $request->getIPAddress();

        $maxAttempts = (int) (env('rateLimit.loginMaxAttempts', 5));
        $window      = (int) (env('rateLimit.loginWindowSeconds', 300));

        // Allow $maxAttempts per $window seconds per IP
        if (! $throttler->check('login_' . $ip, $maxAttempts, $window)) {
            $retryAfter = $throttler->getTokenTime();

            return response()
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $retryAfter)
                ->setContentType('application/json')
                ->setJSON([
                    'success'     => false,
                    'message'     => 'Te veel inlogpogingen. Probeer het opnieuw na ' . $retryAfter . ' seconden.',
                    'retry_after' => $retryAfter,
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Not used
    }
}
