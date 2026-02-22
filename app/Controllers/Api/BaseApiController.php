<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

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

    /**
     * Return the authenticated user's ID from the JWT payload.
     * Can only be called inside routes protected by JwtFilter.
     */
    protected function getAuthUserId(): int
    {
        return (int) $this->request->jwtPayload->user_id;
    }

    /**
     * Return whether authenticated user has premium from JWT payload.
     */
    protected function getAuthUserPremium(): bool
    {
        return (bool) ($this->request->jwtPayload->premium ?? false);
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
    protected function error(string $message, int $status = 400): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->respond([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * Get JSON body from request and validate required fields
     *
     * @param  string[]            $required List of required field names
     * @return array<string, mixed>|false    Returns false if validation fails
     */
    protected function getJsonBody(array $required = []): array|false
    {
        $body = $this->request->getJSON(true) ?? [];

        foreach ($required as $field) {
            if (! isset($body[$field]) || $body[$field] === '') {
                $this->error("Veld '{$field}' is verplicht.");
                return false;
            }
        }

        return $body;
    }
}
