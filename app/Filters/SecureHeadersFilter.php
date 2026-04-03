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
        if ($request->getMethod() === 'options' && $this->isApiRequest($request)) {
            $origin = $this->resolveAllowedOrigin($request->getHeaderLine('Origin'));

            if ($origin === null) {
                return response()->setStatusCode(403);
            }

            return response()
                ->setStatusCode(204)
                ->setHeader('Access-Control-Allow-Origin', $origin)
                ->setHeader('Vary', 'Origin')
                ->setHeader('Access-Control-Allow-Credentials', 'true')
                ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Api-Key, X-Timestamp, X-Signature, X-Device-Id')
                ->setHeader('Access-Control-Max-Age', '86400');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $isApiRequest  = $this->isApiRequest($request);
        $allowedOrigin = $isApiRequest ? $this->resolveAllowedOrigin($request->getHeaderLine('Origin')) : null;

        $response
            ->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'DENY')
            ->setHeader('Referrer-Policy', 'no-referrer')
            ->setHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=(), fullscreen=(self)')
            ->setHeader('Content-Security-Policy', $isApiRequest ? $this->getApiCsp() : $this->getAdminCsp());

        if ($isApiRequest) {
            $response
                ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Api-Key, X-Timestamp, X-Signature, X-Device-Id');
        }

        if ($isApiRequest && $allowedOrigin !== null) {
            $response
                ->setHeader('Access-Control-Allow-Origin', $allowedOrigin)
                ->setHeader('Access-Control-Allow-Credentials', 'true')
                ->appendHeader('Vary', 'Origin');
        }

        if (in_array(trim($request->getUri()->getPath(), '/'), ['api/login', 'api/logout', 'api/refresh'], true)) {
            $response->setHeader('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    private function isApiRequest(RequestInterface $request): bool
    {
        return str_starts_with(trim($request->getUri()->getPath(), '/'), 'api/');
    }

    private function getApiCsp(): string
    {
        $connectSrc = implode(' ', $this->getAllowedOrigins());

        return "default-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; connect-src 'self' {$connectSrc}; img-src 'self' data:; style-src 'self'; script-src 'self'; object-src 'none'";
    }

    private function getAdminCsp(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "connect-src 'self' https://cdn.jsdelivr.net",
            "img-src 'self' data:",
            "font-src 'self' https://cdn.jsdelivr.net data:",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "style-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "style-src-attr 'self' 'unsafe-inline'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "script-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "object-src 'none'",
        ]);
    }

    /**
     * @return list<string>
     */
    private function getAllowedOrigins(): array
    {
        $configured = trim((string) env('cors.allowedOrigins', 'https://app.play2tv.nl,https://dashboard.play2tv.nl'));

        return array_values(array_filter(array_map('trim', explode(',', $configured))));
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
}
