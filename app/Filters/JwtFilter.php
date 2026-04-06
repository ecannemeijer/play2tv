<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\AuthContext;
use App\Libraries\AuthTokenService;
use App\Libraries\SecurityEventService;
use App\Libraries\SecurityException;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;

/**
 * JwtFilter
 *
 * Validates the Authorization: Bearer <token> header on every protected API route.
 *
 * On success:  Sets $request->jwtPayload with decoded token data.
 * On failure:  Returns HTTP 401 JSON response immediately.
 *
 * Android app must send:
 *   Authorization: Bearer eyJhbGciOiJIUzI1NiJ9...
 *
 * Token payload contains:
 *   - user_id   int
 *   - premium   bool
 *   - exp       int (Unix timestamp)
 *   - iat       int
 */
class JwtFilter implements FilterInterface
{
    /**
     * @param RequestInterface|\CodeIgniter\HTTP\IncomingRequest $request
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        AuthContext::clear();
        $tokens      = new AuthTokenService();
        $events      = new SecurityEventService();
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || ! str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Missing or invalid Authorization header.');
        }

        $token    = substr($authHeader, 7);
    $deviceId = $this->resolveDeviceId($request);

        try {
            $decoded = $tokens->validateAccessToken(
                $token,
                $request->getIPAddress(),
                $request instanceof IncomingRequest ? $request->getUserAgent()->getAgentString() : '',
                $deviceId
            );
            AuthContext::set($decoded);

            $userModel = new UserModel();
            $user      = $userModel->find((int) ($decoded->sub ?? $decoded->user_id ?? 0));

            if (! $user || ! $user['is_active']) {
                return $this->unauthorized('Account is deactivated.');
            }
        } catch (SecurityException $e) {
            $events->log('access_token_denied', 'warning', $request instanceof IncomingRequest ? $request : null, null, [
                'ip' => $request->getIPAddress(),
                'user_agent' => $request instanceof IncomingRequest ? $request->getUserAgent()->getAgentString() : '',
                'reason' => $e->getMessage(),
            ]);
            return $this->unauthorized($e->getMessage());
        } catch (\Throwable $e) {
            $events->log('access_token_invalid', 'warning', $request instanceof IncomingRequest ? $request : null, null, [
                'ip' => $request->getIPAddress(),
                'user_agent' => $request instanceof IncomingRequest ? $request->getUserAgent()->getAgentString() : '',
                'reason' => $e->getMessage(),
            ]);
            return $this->unauthorized('Invalid token.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        AuthContext::clear();

        return $response;
    }

    /**
     * Return a 401 JSON response
     */
    private function unauthorized(string $message): ResponseInterface
    {
        return $this->withCorsHeaders(response())
            ->setStatusCode(401)
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

    private function resolveDeviceId(RequestInterface $request): ?string
    {
        $headerDeviceId = trim($request->getHeaderLine('X-Device-Id'));
        if ($headerDeviceId !== '') {
            return $headerDeviceId;
        }

        if ($request instanceof IncomingRequest) {
            foreach (['current_device_id', 'device_id'] as $key) {
                $queryValue = trim((string) ($request->getGet($key) ?? ''));
                if ($queryValue !== '') {
                    return $queryValue;
                }
            }

            $postValue = trim((string) ($request->getPost('device_id') ?? ''));
            if ($postValue !== '') {
                return $postValue;
            }

            $rawBody = trim((string) $request->getBody());
            if ($rawBody !== '') {
                try {
                    $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
                    $bodyDeviceId = trim((string) ($decoded['device_id'] ?? $decoded['current_device_id'] ?? ''));
                    if ($bodyDeviceId !== '') {
                        return $bodyDeviceId;
                    }
                } catch (\JsonException) {
                }
            }
        }

        return null;
    }
}
