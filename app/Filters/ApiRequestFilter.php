<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use JsonException;

class ApiRequestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $method = strtoupper($request->getMethod());

        if (in_array($method, ['TRACE', 'TRACK', 'CONNECT'], true)) {
            return $this->jsonError('HTTP-methode niet toegestaan.', 405);
        }

        if (str_starts_with(trim($request->getUri()->getPath(), '/'), 'api/')
            && ! $request->isSecure()
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
        return response()
            ->setStatusCode($status)
            ->setContentType('application/json')
            ->setJSON([
                'success' => false,
                'message' => $message,
            ]);
    }
}