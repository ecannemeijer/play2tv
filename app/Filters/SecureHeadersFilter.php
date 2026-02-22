<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * SecureHeadersFilter
 *
 * Adds security-related HTTP response headers to every response.
 * Apply this filter globally via app/Config/Filters.php.
 *
 * Headers added:
 *   - Strict-Transport-Security (HSTS)
 *   - X-Content-Type-Options
 *   - X-Frame-Options
 *   - X-XSS-Protection
 *   - Referrer-Policy
 *   - Content-Security-Policy
 *   - Permissions-Policy
 *   - Access-Control-Allow-Origin (CORS)
 */
class SecureHeadersFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Handle preflight OPTIONS requests for CORS
        if ($request->getMethod() === 'options') {
            return response()
                ->setStatusCode(204)
                ->setHeader('Access-Control-Allow-Origin', env('cors.allowedOrigins', '*'))
                ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->setHeader('Access-Control-Max-Age', '86400');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $allowedOrigin = env('cors.allowedOrigins', '*');

        $isLocal = in_array(getenv('CI_ENVIRONMENT') ?: env('CI_ENVIRONMENT', 'production'), ['development', 'testing']);

        $response
            // Only set HSTS in production (HTTPS required)
            ->setHeader('Strict-Transport-Security', $isLocal ? 'max-age=0' : 'max-age=31536000; includeSubDomains; preload')
            // Prevent MIME sniffing
            ->setHeader('X-Content-Type-Options', 'nosniff')
            // Prevent clickjacking
            ->setHeader('X-Frame-Options', 'SAMEORIGIN')
            // Legacy XSS protection
            ->setHeader('X-XSS-Protection', '1; mode=block')
            // Referrer policy
            ->setHeader('Referrer-Policy', 'no-referrer-when-downgrade')
            // Minimal permissions policy
            ->setHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()')
            // CORS
            ->setHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        return $response;
    }
}
