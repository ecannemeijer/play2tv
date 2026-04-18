<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use JsonException;

class ApiRequestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $path = trim($request->getUri()->getPath(), '/');
        $method = strtoupper($request->getMethod());
        $isSecureRequest = $request instanceof IncomingRequest ? $request->isSecure() : true;

        if (in_array($method, ['TRACE', 'TRACK', 'CONNECT'], true)) {
            return $this->jsonError('HTTP-methode niet toegestaan.', 405);
        }

        if (str_starts_with($path, 'api/')
            && ! $isSecureRequest
            && (bool) env('security.rejectInsecureApiRequests', ENVIRONMENT === 'production')) {
            return $this->jsonError('HTTPS is verplicht voor API-verkeer.', 400);
        }

        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        $contentType = strtolower($request->getHeaderLine('Content-Type'));
        $body        = trim((string) $request->getBody());

        if ($body === '') {
            return null;
        }

        if ($this->allowsLegacyFormBody($path, $contentType)) {
            return null;
        }

        if (! str_contains($contentType, 'application/json')) {
            return $this->jsonError('API schrijft alleen application/json verzoeken toe.', 415);
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->jsonError('Ongeldige JSON payload.', 400);
        }

        if (! is_array($decoded)) {
            return $this->jsonError('JSON body moet een object of array zijn.', 400);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
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
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Api-Key, X-Velixa-API-Key, X-Timestamp, X-Signature, X-Device-Id');

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

    private function allowsLegacyFormBody(string $path, string $contentType): bool
    {
        if (! in_array($path, ['api/login', 'api/register', 'api/refresh', 'api/logout', 'api/diagnostics/upload'], true)) {
            return false;
        }

        return str_contains($contentType, 'application/x-www-form-urlencoded')
            || str_contains($contentType, 'multipart/form-data')
            || $contentType === '';
    }
}