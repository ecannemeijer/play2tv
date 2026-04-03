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
    private string $issuer;
    private string $audience;

    public function __construct()
    {
        $this->secret        = (string) env('jwt.secret', '');
        $this->expirySeconds = (int) env('jwt.accessTtl', 900);
        $this->issuer        = (string) env('jwt.issuer', 'play2tv-api');
        $this->audience      = (string) env('jwt.audience', 'play2tv-clients');

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
        return $this->generateAccessToken(
            $userId,
            'user',
            $premium,
            1,
            '',
            '',
            bin2hex(random_bytes(16))
        );
    }

    public function generateAccessToken(
        int $userId,
        string $role,
        bool $premium,
        int $authVersion,
        string $fingerprintHash,
        string $familyId,
        string $jwtId
    ): string
    {
        $now = time();

        $payload = [
            'iss'     => $this->issuer,
            'aud'     => $this->audience,
            'iat'     => $now,
            'nbf'     => $now,
            'exp'     => $now + $this->expirySeconds,
            'typ'     => 'access',
            'jti'     => $jwtId,
            'sub'     => $userId,
            'user_id' => $userId,
            'role'    => $role,
            'premium' => $premium,
            'av'      => $authVersion,
            'fp'      => $fingerprintHash,
            'fam'     => $familyId,
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

    public function getAccessTtl(): int
    {
        return $this->expirySeconds;
    }
}
