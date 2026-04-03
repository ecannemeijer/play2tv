<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\AuthContext;
use CodeIgniter\RESTful\ResourceController;
use JsonException;

/**
 * BaseApiController
 *
 * Base class for all API controllers.
 * Provides common helper methods for JSON responses and authentication.
 *
 * JSON Response conventions:
 *   Success: {"success": true, "data": {...}}
 *   Error:   {"success": false, "message": "Error text"}
 */
class BaseApiController extends ResourceController
{
    protected $format = 'json';
    protected array $validationErrors = [];

    /**
     * Return the authenticated user's ID from the JWT payload.
     * Can only be called inside routes protected by JwtFilter.
     */
    protected function getAuthUserId(): int
    {
        return (int) (AuthContext::get()?->user_id ?? 0);
    }

    /**
     * Return whether authenticated user has premium from JWT payload.
     */
    protected function getAuthUserPremium(): bool
    {
        return (bool) (AuthContext::get()?->premium ?? false);
    }

    protected function getAuthRole(): string
    {
        return (string) (AuthContext::get()?->role ?? 'user');
    }

    /**
     * Shorthand: return 200 success JSON
     *
     * @param array<string, mixed> $data
     */
    protected function ok(array $data = [], string $message = 'OK'): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->respond([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], 200);
    }

    /**
     * Shorthand: return 201 created JSON
     *
     * @param array<string, mixed> $data
     */
    protected function created(array $data = [], string $message = 'Created'): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->respond([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], 201);
    }

    /**
     * Shorthand: return error JSON with given status code
     */
    protected function error(string $message, int $status = 400, array $errors = []): \CodeIgniter\HTTP\ResponseInterface
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return $this->respond($payload, $status);
    }

    /**
     * Get JSON body from request and validate required fields
     *
     * @param  string[]            $required List of required field names
     * @return array<string, mixed>|false    Returns false if validation fails
     */
    protected function getJsonBody(array $required = []): array|false
    {
        $this->validationErrors = [];
        $path = trim($this->request->getUri()->getPath(), '/');
        $contentType = strtolower($this->request->getHeaderLine('Content-Type'));
        $rawBody     = trim((string) $this->request->getBody());

        if ($this->allowsLegacyFormBody($path, $contentType)) {
            $body = $this->request->getPost();
            if (! is_array($body)) {
                $body = [];
            }

            foreach ($required as $field) {
                if (! isset($body[$field]) || $body[$field] === '') {
                    $this->validationErrors[$field] = "Veld '{$field}' is verplicht.";
                    return false;
                }
            }

            return $body;
        }

        if ($rawBody !== '' && ! str_contains($contentType, 'application/json')) {
            $this->validationErrors = ['content_type' => 'Gebruik application/json voor API-aanvragen.'];
            return false;
        }

        if ($rawBody === '') {
            $body = [];
        } else {
            try {
                $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $this->validationErrors = ['body' => 'Ongeldige JSON payload.'];
                return false;
            }

            if (! is_array($decoded)) {
                $this->validationErrors = ['body' => 'JSON payload moet een object zijn.'];
                return false;
            }

            $body = $decoded;
        }

        foreach ($required as $field) {
            if (! isset($body[$field]) || $body[$field] === '') {
                $this->validationErrors[$field] = "Veld '{$field}' is verplicht.";
                return false;
            }
        }

        return $body;
    }

    private function allowsLegacyFormBody(string $path, string $contentType): bool
    {
        if (! in_array($path, ['api/login', 'api/register', 'api/refresh', 'api/logout'], true)) {
            return false;
        }

        return str_contains($contentType, 'application/x-www-form-urlencoded')
            || str_contains($contentType, 'multipart/form-data')
            || $contentType === '';
    }

    protected function validatePayload(array $payload, array $rules, array $messages = []): bool
    {
        $validation = service('validation');
        $validation->reset()->setRules($rules, $messages);

        if (! $validation->run($payload)) {
            $this->validationErrors = $validation->getErrors();
            return false;
        }

        $this->validationErrors = [];

        return true;
    }

    protected function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    protected function sanitizeText(?string $value, int $maxLength = 255): string
    {
        $clean = trim(strip_tags((string) $value));
        $clean = preg_replace('/[[:cntrl:]]/', '', $clean) ?? '';

        return substr($clean, 0, $maxLength);
    }

    protected function rateLimitError(string $message, int $retryAfter): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response
            ->setStatusCode(429)
            ->setHeader('Retry-After', (string) $retryAfter)
            ->setJSON([
                'success'     => false,
                'message'     => $message,
                'retry_after' => $retryAfter,
            ]);
    }
}
