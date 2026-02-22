<?php

declare(strict_types=1);

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JwtLibrary
 *
 * Centralized JWT token generation and decoding.
 *
 * Token payload structure:
 * {
 *   "iss": "play2tv-api",
 *   "iat": 1700000000,
 *   "exp": 1700604800,
 *   "user_id": 42,
 *   "premium": true
 * }
 *
 * Android app usage:
 *   Store token in SharedPreferences / EncryptedSharedPreferences
 *   Send as: Authorization: Bearer {token}
 */
class JwtLibrary
{
    private string $secret;
    private int    $expirySeconds;

    public function __construct()
    {
        $this->secret        = env('jwt.secret', '');
        $this->expirySeconds = (int) env('jwt.expiry', 604800); // 7 days default

        if (empty($this->secret)) {
            throw new \RuntimeException('JWT secret is not configured in .env');
        }
    }

    /**
     * Generate a signed JWT token for a user
     *
     * @param int  $userId  User ID
     * @param bool $premium Whether user has active premium
     * @return string Signed JWT string
     */
    public function generate(int $userId, bool $premium): string
    {
        $now = time();

        $payload = [
            'iss'     => 'play2tv-api',
            'iat'     => $now,
            'exp'     => $now + $this->expirySeconds,
            'user_id' => $userId,
            'premium' => $premium,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Decode and validate a token
     *
     * @throws \Firebase\JWT\ExpiredException
     * @throws \Firebase\JWT\SignatureInvalidException
     */
    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->secret, 'HS256'));
    }
}
