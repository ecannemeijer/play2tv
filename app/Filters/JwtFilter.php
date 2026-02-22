<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
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
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || ! str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Missing or invalid Authorization header.');
        }

        $token  = substr($authHeader, 7);
        $secret = getenv('jwt.secret') ?: env('jwt.secret', '');

        if (empty($secret)) {
            log_message('critical', 'JWT secret is not configured in .env');
            return $this->unauthorized('Server configuration error.');
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            // Attach decoded payload to request so controllers can use it
            $request->jwtPayload = $decoded;

            // Verify user is still active
            $userModel = new UserModel();
            $user      = $userModel->find($decoded->user_id);

            if (! $user || ! $user['is_active']) {
                return $this->unauthorized('Account is deactivated.');
            }

        } catch (ExpiredException $e) {
            return $this->unauthorized('Token has expired. Please login again.');
        } catch (SignatureInvalidException $e) {
            return $this->unauthorized('Invalid token signature.');
        } catch (\Exception $e) {
            return $this->unauthorized('Invalid token: ' . $e->getMessage());
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Not used
    }

    /**
     * Return a 401 JSON response
     */
    private function unauthorized(string $message): ResponseInterface
    {
        return response()
            ->setStatusCode(401)
            ->setContentType('application/json')
            ->setJSON([
                'success' => false,
                'message' => $message,
            ]);
    }
}
