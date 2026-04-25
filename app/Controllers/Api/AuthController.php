<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\TelemetryConfigProvider;
use App\Models\UserModel;
use App\Models\UserDeviceModel;
use App\Models\UserIpsLogModel;
use App\Libraries\AuthTokenService;
use App\Libraries\JwtLibrary;
use App\Libraries\SecurityEventService;
use App\Libraries\SecurityException;
use App\Libraries\SecurityThrottleService;

/**
 * AuthController
 *
 * Handles user authentication for the Play2TV Android app.
 *
 * Endpoints:
 *   POST /api/register  → Create new account
 *   POST /api/login     → Authenticate, returns JWT
 *   POST /api/logout    → Client-side token deletion (stateless)
 *   GET  /api/user      → Get current user profile (JWT required)
 *
 * Android example (Retrofit):
 *   @POST("api/login")
 *   suspend fun login(@Body body: LoginRequest): Response<LoginResponse>
 *
 *   data class LoginRequest(val email: String, val password: String, val device_id: String)
 *   data class LoginResponse(val success: Boolean, val data: LoginData)
 *   data class LoginData(val token: String, val premium: Boolean, val premium_until: String?)
 */
class AuthController extends BaseApiController
{
    private const DUMMY_PASSWORD_HASH = '$2y$12$kyUUdk/Hc5LpFmAyTACTc.MC/3Xpgwhhepmxyg3DXKsTROPOMOvE.';

    private UserModel       $userModel;
    private UserDeviceModel $deviceModel;
    private UserIpsLogModel $ipsLogModel;
    private JwtLibrary      $jwt;
    private AuthTokenService $tokens;
    private SecurityThrottleService $throttle;
    private SecurityEventService $events;
    private TelemetryConfigProvider $telemetryConfig;

    public function __construct()
    {
        $this->userModel   = new UserModel();
        $this->deviceModel = new UserDeviceModel();
        $this->ipsLogModel = new UserIpsLogModel();
        $this->jwt         = new JwtLibrary();
        $this->tokens      = new AuthTokenService();
        $this->throttle    = new SecurityThrottleService();
        $this->events      = new SecurityEventService();
        $this->telemetryConfig = new TelemetryConfigProvider();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/register
    //
    // Body (JSON):
    //   { "email": "user@example.com", "password": "secret123", "device_id": "..." }
    //
    // Response 201:
    //   { "success": true, "data": { "token": "eyJ...", "premium": false } }
    // ─────────────────────────────────────────────────────────────────────────
    public function register()
    {
        $body = $this->getJsonBody(['email', 'password']);
        log_message('debug', 'AuthController::register payload received email={email} device_id={device_id}', [
            'email' => (string) ($body['email'] ?? ''),
            'device_id' => (string) ($body['device_id'] ?? ''),
        ]);
        if ($body === false) {
            log_message('warning', 'AuthController::register invalid payload');
            return $this->error('Ongeldige registratiepayload.', 422, $this->getValidationErrors());
        }

        if (! $this->validatePayload($body, [
            'email'     => 'required|valid_email|max_length[255]',
            'password'  => 'required|min_length[12]|max_length[255]',
            'device_id' => 'permit_empty|max_length[255]',
        ])) {
            log_message('warning', 'AuthController::register validation failed errors={errors}', [
                'errors' => json_encode($this->getValidationErrors(), JSON_UNESCAPED_UNICODE),
            ]);
            return $this->error('Registratievalidatie mislukt.', 422, $this->getValidationErrors());
        }

        $email    = strtolower($this->sanitizeText((string) $body['email'], 255));
        $password = (string) $body['password'];
        $deviceId = $this->sanitizeText((string) ($body['device_id'] ?? ''), 255) ?: null;
        $ip       = $this->request->getIPAddress();
        $userAgent = $this->request->getUserAgent()->getAgentString();

        if ($this->userModel->findByEmail($email)) {
            log_message('warning', 'AuthController::register duplicate email email={email}', ['email' => $email]);
            return $this->error('Dit e-mailadres is al in gebruik.', 409);
        }

        $userId = $this->userModel->insert([
            'email'    => $email,
            'password' => $password,
            'role'     => 'user',
            'premium'  => 0,
        ]);

        if (! $userId) {
            log_message('error', 'AuthController::register insert failed email={email}', ['email' => $email]);
            return $this->error('Registratie mislukt. Probeer opnieuw.', 500);
        }

        $user = $this->userModel->find($userId);
        if ($user === null) {
            log_message('error', 'AuthController::register user lookup failed user_id={user_id}', ['user_id' => $userId]);
            return $this->error('Registratie mislukt. Probeer opnieuw.', 500);
        }

        // NOTE: Do NOT auto-register devices on account creation.
        // Device registration is handled exclusively by the IPTV player app
        // via POST /api/devices/register.

        $this->ipsLogModel->log(
            (int) $userId,
            $ip,
            $userAgent
        );

        $tokenPair = $this->tokens->issueTokenPair($user, false, $deviceId, $ip, $userAgent);

        log_message('debug', 'AuthController::register success user_id={user_id} email={email} device_id={device_id}', [
            'user_id' => (int) $userId,
            'email' => $email,
            'device_id' => (string) ($deviceId ?? ''),
        ]);

        return $this->respond([
            'success' => true,
            'message' => 'Registratie geslaagd.',
            'data'    => [
                'user_id'       => $userId,
                'email'         => $email,
                'role'          => $user['role'] ?? 'user',
                'premium'       => false,
                'premium_until' => null,
                ...$this->buildLegacyClientPayload($user),
                ...$tokenPair,
            ],
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/login
    //
    // Body (JSON):
    //   { "email": "user@example.com", "password": "secret123", "device_id": "..." }
    //
    // Response 200:
    //   {
    //     "success": true,
    //     "data": {
    //       "token": "eyJ...",
    //       "user_id": 1,
    //       "email": "user@example.com",
    //       "premium": true,
    //       "premium_until": "2025-12-31 00:00:00"
    //     }
    //   }
    // ─────────────────────────────────────────────────────────────────────────
    public function login()
    {
        $body = $this->getJsonBody(['email', 'password']);
        if ($body === false) {
            return $this->error('Ongeldige loginpayload.', 422, $this->getValidationErrors());
        }

        if (! $this->validatePayload($body, [
            'email'     => 'required|valid_email|max_length[255]',
            'password'  => 'required|max_length[255]',
            'device_id' => 'permit_empty|max_length[255]',
        ])) {
            return $this->error('Loginvalidatie mislukt.', 422, $this->getValidationErrors());
        }

        $email     = strtolower($this->sanitizeText((string) $body['email'], 255));
        $password  = (string) $body['password'];
        $deviceId  = $this->sanitizeText((string) ($body['device_id'] ?? ''), 255) ?: null;
        $ip        = $this->request->getIPAddress();
        $userAgent = $this->request->getUserAgent()->getAgentString();
        $retryAfter = $this->throttle->getLoginRetryAfter($email, $ip);

        if ($retryAfter > 0) {
            return $this->rateLimitError('Te veel inlogpogingen. Wacht voordat je opnieuw probeert.', $retryAfter);
        }

        $user = $this->userModel->findByEmail($email);

        $isValidPassword = $user !== null
            ? $this->userModel->verifyPassword($password, $user['password'])
            : password_verify($password, self::DUMMY_PASSWORD_HASH);

        if (! $user || ! $isValidPassword) {
            $backoff = $this->throttle->recordLoginFailure($email, $ip);
            if ($user !== null && $backoff > 0) {
                $this->userModel->setLockUntil((int) $user['id'], $backoff);
            }

            $this->events->log('login_failed', 'warning', $this->request, $user['id'] ?? null, [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'email' => $email,
            ]);

            if ($backoff > 0) {
                return $this->rateLimitError('Te veel mislukte inlogpogingen.', $backoff);
            }

            return $this->error('Ongeldige inloggegevens.', 401);
        }

        if (! $user['is_active']) {
            return $this->error('Dit account is geblokkeerd.', 403);
        }

        if ($this->userModel->isLocked($user)) {
            $seconds = max(1, strtotime((string) $user['locked_until']) - time());
            return $this->rateLimitError('Account tijdelijk vergrendeld na mislukte inlogpogingen.', $seconds);
        }

        $isPremium = $this->userModel->isPremium($user);
        $this->throttle->clearLoginFailures($email, $ip);
        $this->userModel->clearLock((int) $user['id']);

        $this->userModel->recordLogin((int) $user['id'], $ip);

        // NOTE: Do NOT auto-register devices on login.
        // Device registration is handled exclusively by the IPTV player app
        // via POST /api/devices/register. The manager app must never consume
        // a device slot.

        $this->ipsLogModel->log((int) $user['id'], $ip, $userAgent);
        $tokenPair = $this->tokens->issueTokenPair($user, $isPremium, $deviceId, $ip, $userAgent);

        return $this->withCorsHeaders($this->respond([
            'success' => true,
            'message' => 'Inloggen geslaagd.',
            'data' => [
                'user_id'       => (int) $user['id'],
                'email'         => $user['email'],
                'role'          => $user['role'] ?? 'user',
                'premium'       => $isPremium,
                'premium_until' => $user['premium_until'],
                ...$this->buildLegacyClientPayload($user),
                ...$tokenPair,
            ],
            'config' => $this->telemetryConfig->getConfig(),
        ], 200));
    }

    public function refresh()
    {
        $body = $this->getJsonBody(['refresh_token']);
        if ($body === false) {
            return $this->error('Ongeldige refresh-payload.', 422, $this->getValidationErrors());
        }

        if (! $this->validatePayload($body, [
            'refresh_token' => 'required|max_length[255]',
            'device_id'     => 'permit_empty|max_length[255]',
        ])) {
            return $this->error('Refresh-validatie mislukt.', 422, $this->getValidationErrors());
        }

        $refreshToken = (string) $body['refresh_token'];
        $deviceId     = $this->sanitizeText((string) ($body['device_id'] ?? ''), 255) ?: null;

        try {
            $tokenPair = $this->tokens->refreshToken(
                $refreshToken,
                $this->request->getIPAddress(),
                $this->request->getUserAgent()->getAgentString(),
                $deviceId
            );
        } catch (SecurityException $exception) {
            return $this->error($exception->getMessage(), $exception->getStatusCode());
        }

        return $this->ok($tokenPair, 'Token vernieuwd.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/logout
    //
    // JWT tokens are stateless so actual invalidation happens client-side.
    // Android app must delete the stored token on logout.
    //
    // Response 200:
    //   { "success": true, "message": "Uitgelogd." }
    // ─────────────────────────────────────────────────────────────────────────
    public function logout()
    {
        $body = $this->getJsonBody();
        $refreshToken = is_array($body) ? (string) ($body['refresh_token'] ?? '') : '';

        if ($refreshToken !== '') {
            $this->tokens->revokeRefreshToken($refreshToken, 'logout');
        }

        $authHeader = $this->request->getHeaderLine('Authorization');
        if (str_starts_with($authHeader, 'Bearer ')) {
            try {
                $payload = $this->jwt->decode(substr($authHeader, 7));
                $this->tokens->revokeFamilyFromPayload($payload, 'logout');
            } catch (\Throwable) {
            }
        }

        return $this->ok([], 'Uitgelogd. Verwijder het token uit de app.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/user
    // Header: Authorization: Bearer {token}
    //
    // Response 200:
    //   {
    //     "success": true,
    //     "data": {
    //       "id": 1,
    //       "email": "user@example.com",
    //       "premium": true,
    //       "premium_until": "2025-12-31",
    //       "created_at": "2024-01-01"
    //     }
    //   }
    // ─────────────────────────────────────────────────────────────────────────
    public function user()
    {
        $userId = $this->getAuthUserId();
        $user   = $this->userModel->find($userId);

        if (! $user) {
            return $this->error('Gebruiker niet gevonden.', 404);
        }

        $isPremium = $this->userModel->isPremium($user);

        return $this->ok([
            'id'             => (int) $user['id'],
            'email'          => $user['email'],
            'role'           => $user['role'] ?? 'user',
            'premium'        => $isPremium,
            'premium_until'  => $user['premium_until'],
            'created_at'     => $user['created_at'],
            'last_login_at'  => $user['last_login_at'],
            ...$this->buildLegacyClientPayload($user),
        ]);
    }

    /**
     * Older Android clients still expect Xtream credentials in auth responses.
     * Keeping these fields preserves compatibility while the app is migrated.
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private function buildLegacyClientPayload(array $user): array
    {
        $playlistUrl = base_url('api/playlist');

        return [
            'xtream_server' => $user['xtream_server'] ?? null,
            'xtream_username' => $user['xtream_username'] ?? null,
            'xtream_password' => $user['xtream_password'] ?? null,
            'has_xtream_password' => ! empty($user['xtream_password']),
            'playlist_url' => $playlistUrl,
        ];
    }
}
