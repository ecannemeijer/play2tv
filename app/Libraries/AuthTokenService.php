<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\AuthRefreshTokenModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\IncomingRequest;

class AuthTokenService
{
    private AuthRefreshTokenModel $refreshTokens;
    private UserModel $userModel;
    private JwtLibrary $jwt;
    private DeviceFingerprintService $fingerprints;
    private SecurityEventService $events;
    private int $refreshTtl;
    private int $maxConcurrentSessions;
    private string $refreshPepper;

    public function __construct()
    {
        $this->refreshTokens         = new AuthRefreshTokenModel();
        $this->userModel             = new UserModel();
        $this->jwt                   = new JwtLibrary();
        $this->fingerprints          = new DeviceFingerprintService();
        $this->events                = new SecurityEventService();
        $this->refreshTtl            = (int) env('auth.refreshTtl', 60 * 60 * 24 * 30);
        $this->maxConcurrentSessions = max(1, (int) env('auth.maxConcurrentSessions', 3));
        $this->refreshPepper         = (string) env('auth.refreshPepper', env('jwt.secret', 'play2tv-refresh-pepper'));
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function issueTokenPair(array $user, bool $isPremium, ?string $deviceId, string $ipAddress, string $userAgent): array
    {
        return $this->issuePair($user, $isPremium, $deviceId, $ipAddress, $userAgent);
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshToken(string $refreshToken, string $ipAddress, string $userAgent, ?string $deviceId = null): array
    {
        [$selector, $validator] = $this->parseRefreshToken($refreshToken);
        $row                    = $this->refreshTokens->findBySelector($selector);

        if ($row === null) {
            throw new SecurityException('Ongeldig refresh token.', 401);
        }

        $candidateHash = $this->hashRefreshValidator($validator);
        if (! hash_equals((string) $row['token_hash'], $candidateHash)) {
            $this->events->log('refresh_token_invalid_validator', 'warning', null, (int) $row['user_id'], [
                'ip' => $ipAddress,
                'user_agent' => $userAgent,
                'selector' => $selector,
            ]);
            throw new SecurityException('Ongeldig refresh token.', 401);
        }

        if ($row['revoked_at'] !== null || strtotime((string) $row['expires_at']) <= time()) {
            $this->compromiseTokenFamily($row, 'refresh_token_reuse_detected', $ipAddress, $userAgent, $deviceId);
            throw new SecurityException('Refresh token is ingetrokken.', 401);
        }

        $user = $this->userModel->find((int) $row['user_id']);
        if ($user === null || ! (bool) $user['is_active']) {
            $this->refreshTokens->revokeFamily((string) $row['family_id'], 'user_inactive');
            throw new SecurityException('Account is niet beschikbaar.', 401);
        }

        $currentFingerprint = $this->fingerprints->buildFingerprintHash($userAgent, $ipAddress, $deviceId);
        if (! hash_equals((string) $row['fingerprint_hash'], $currentFingerprint)) {
            $this->compromiseTokenFamily($row, 'refresh_token_fingerprint_mismatch', $ipAddress, $userAgent, $deviceId);
            throw new SecurityException('Verdachte sessie gedetecteerd.', 401);
        }

        $isPremium = $this->userModel->isPremium($user);
        $pair      = $this->issuePair($user, $isPremium, $deviceId, $ipAddress, $userAgent, (string) $row['family_id']);
        $this->refreshTokens->revokeById((int) $row['id'], 'rotated', $this->extractSelector((string) $pair['refresh_token']));

        return $pair;
    }

    public function validateAccessToken(string $accessToken, string $ipAddress, string $userAgent, ?string $deviceId = null): object
    {
        $payload = $this->jwt->decode($accessToken);

        if (($payload->typ ?? null) !== 'access') {
            throw new SecurityException('Ongeldig access token.', 401);
        }

        $userId = (int) ($payload->sub ?? 0);
        $user   = $this->userModel->find($userId);

        if ($user === null || ! (bool) $user['is_active']) {
            throw new SecurityException('Account is gedeactiveerd.', 401);
        }

        if ((int) ($payload->av ?? 0) !== (int) ($user['auth_version'] ?? 1)) {
            throw new SecurityException('Sessie is ingetrokken.', 401);
        }

        $expectedFingerprint = $this->fingerprints->buildFingerprintHash($userAgent, $ipAddress, $deviceId);
        if (! hash_equals((string) ($payload->fp ?? ''), $expectedFingerprint)) {
            $this->compromiseUserSessions($userId, (string) ($payload->fam ?? ''), 'access_token_fingerprint_mismatch', $ipAddress, $userAgent, $deviceId);
            throw new SecurityException('Verdachte sessie gedetecteerd.', 401);
        }

        return $payload;
    }

    public function revokeFamilyFromPayload(object $payload, string $reason = 'logout'): void
    {
        $familyId = (string) ($payload->fam ?? '');

        if ($familyId !== '') {
            $this->refreshTokens->revokeFamily($familyId, $reason);
        }
    }

    public function revokeRefreshToken(string $refreshToken, string $reason = 'logout'): void
    {
        [$selector, $validator] = $this->parseRefreshToken($refreshToken);
        $row                    = $this->refreshTokens->findBySelector($selector);

        if ($row === null) {
            return;
        }

        if (hash_equals((string) $row['token_hash'], $this->hashRefreshValidator($validator))) {
            $this->refreshTokens->revokeFamily((string) $row['family_id'], $reason);
        }
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private function issuePair(
        array $user,
        bool $isPremium,
        ?string $deviceId,
        string $ipAddress,
        string $userAgent,
        ?string $familyId = null
    ): array {
        $familyId        ??= $this->randomToken(16);
        $selector          = $this->randomToken(12);
        $validator         = $this->randomToken(32);
        $fingerprintHash   = $this->fingerprints->buildFingerprintHash($userAgent, $ipAddress, $deviceId);
        $accessJti         = $this->randomToken(16);
        $refreshExpiresAt  = date('Y-m-d H:i:s', time() + $this->refreshTtl);
        $role              = (string) ($user['role'] ?? 'user');
        $authVersion       = (int) ($user['auth_version'] ?? 1);

        $this->refreshTokens->insert([
            'user_id'          => (int) $user['id'],
            'selector'         => $selector,
            'family_id'        => $familyId,
            'token_hash'       => $this->hashRefreshValidator($validator),
            'fingerprint_hash' => $fingerprintHash,
            'ip_hash'          => $this->fingerprints->buildIpHash($ipAddress),
            'user_agent_hash'  => $this->fingerprints->buildUserAgentHash($userAgent),
            'device_id'        => $deviceId,
            'access_jti'       => $accessJti,
            'expires_at'       => $refreshExpiresAt,
            'last_used_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->enforceConcurrentSessionLimit((int) $user['id']);

        $accessToken = $this->jwt->generateAccessToken(
            (int) $user['id'],
            $role,
            $isPremium,
            $authVersion,
            $fingerprintHash,
            $familyId,
            $accessJti
        );

        return [
            'token'              => $accessToken,
            'access_token'       => $accessToken,
            'token_type'         => 'Bearer',
            'expires_in'         => $this->jwt->getAccessTtl(),
            'refresh_token'      => $selector . '.' . $validator,
            'refresh_expires_in' => $this->refreshTtl,
            'refresh_expires_at' => $refreshExpiresAt,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function compromiseTokenFamily(array $row, string $reason, string $ipAddress, string $userAgent, ?string $deviceId): void
    {
        $this->compromiseUserSessions((int) $row['user_id'], (string) $row['family_id'], $reason, $ipAddress, $userAgent, $deviceId);
    }

    private function compromiseUserSessions(int $userId, string $familyId, string $reason, string $ipAddress, string $userAgent, ?string $deviceId): void
    {
        if ($familyId !== '') {
            $this->refreshTokens->revokeFamily($familyId, $reason);
        } else {
            $this->refreshTokens->revokeUserTokens($userId, $reason);
        }

        $this->userModel->bumpAuthVersion($userId);
        $this->events->log($reason, 'critical', null, $userId, [
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'device_id' => $deviceId,
        ]);
    }

    private function enforceConcurrentSessionLimit(int $userId): void
    {
        $activeTokens = $this->refreshTokens->getActiveForUser($userId);
        $overflow     = count($activeTokens) - $this->maxConcurrentSessions;

        if ($overflow <= 0) {
            return;
        }

        for ($index = 0; $index < $overflow; $index++) {
            $token = $activeTokens[$index] ?? null;

            if ($token !== null) {
                $this->refreshTokens->revokeById((int) $token['id'], 'session_limit_exceeded');
            }
        }
    }

    /**
     * @return array{0:string,1:string}
     */
    private function parseRefreshToken(string $refreshToken): array
    {
        $parts = explode('.', trim($refreshToken), 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new SecurityException('Ongeldig refresh token.', 401);
        }

        return [$parts[0], $parts[1]];
    }

    private function extractSelector(string $refreshToken): string
    {
        return explode('.', $refreshToken, 2)[0] ?? '';
    }

    private function hashRefreshValidator(string $validator): string
    {
        return hash_hmac('sha256', $validator, $this->refreshPepper);
    }

    private function randomToken(int $bytes): string
    {
        return bin2hex(random_bytes($bytes));
    }
}