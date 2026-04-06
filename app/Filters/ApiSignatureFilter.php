<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\SecurityEventService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiSignatureFilter implements FilterInterface
{
    private SecurityEventService $events;

    public function __construct()
    {
        $this->events = new SecurityEventService();
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return null;
        }

        $apiKey           = trim((string) env('api.clientKey', ''));
        $signatureSecret  = trim((string) env('api.signatureSecret', ''));
        $providedApiKey   = trim($request->getHeaderLine('X-Api-Key'));

        if ($apiKey !== '' && ! hash_equals($apiKey, $providedApiKey)) {
            $this->logFailure($request, 'api_key_invalid');
            return $this->jsonError('Ongeldige API-sleutel.', 401);
        }

        if ($signatureSecret === '') {
            return null;
        }

        $timestamp = trim($request->getHeaderLine('X-Timestamp'));
        $signature = trim($request->getHeaderLine('X-Signature'));
        if ($timestamp === '' || $signature === '') {
            $this->logFailure($request, 'request_signature_missing');
            return $this->jsonError('Verplichte request-signature ontbreekt.', 401);
        }

        $timestampInt = ctype_digit($timestamp) ? (int) $timestamp : 0;
        $allowedSkew  = (int) env('api.signatureAllowedClockSkew', 300);
        if ($timestampInt <= 0 || abs(time() - $timestampInt) > $allowedSkew) {
            $this->logFailure($request, 'request_signature_stale');
            return $this->jsonError('Request-signature is verlopen.', 401);
        }

        $bodyHash  = hash('sha256', (string) $request->getBody());
        $canonical = implode("\n", [
            strtoupper($request->getMethod()),
            '/' . trim($request->getUri()->getPath(), '/'),
            $timestamp,
            $bodyHash,
        ]);
        $expected = base64_encode(hash_hmac('sha256', $canonical, $signatureSecret, true));

        if (! hash_equals($expected, $signature)) {
            $this->logFailure($request, 'request_signature_invalid');
            return $this->jsonError('Ongeldige request-signature.', 401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function logFailure(RequestInterface $request, string $event): void
    {
        $this->events->log($event, 'warning', $request instanceof IncomingRequest ? $request : null, null, [
            'ip' => $request->getIPAddress(),
            'user_agent' => $request instanceof IncomingRequest ? $request->getUserAgent()->getAgentString() : '',
        ]);
    }

    private function jsonError(string $message, int $status): ResponseInterface
    {
        return $this->withCorsHeaders(response())
            ->setStatusCode($status)
            ->setContentType('application/json')
            ->setJSON([
                'success' => false,
                'message' => $message,
            ]);
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
        $defaultOrigins = [
            'https://app.play2tv.nl',
            'https://dashboard.play2tv.nl',
            'https://user.velixatv.com',
        ];
        $configured = trim((string) env('cors.allowedOrigins', ''));

        if ($configured === '') {
            return $defaultOrigins;
        }

        $configuredOrigins = array_values(array_filter(array_map('trim', explode(',', $configured))));

        return array_values(array_unique([...$defaultOrigins, ...$configuredOrigins]));
    }
}