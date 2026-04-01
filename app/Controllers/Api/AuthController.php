<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\AdminModel;
use App\Models\UserModel;
use App\Models\UserDeviceModel;
use App\Models\UserIpsLogModel;
use App\Libraries\JwtLibrary;

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
    private UserModel       $userModel;
    private UserDeviceModel $deviceModel;
    private UserIpsLogModel $ipsLogModel;
    private JwtLibrary      $jwt;

    public function __construct()
    {
        $this->userModel   = new UserModel();
        $this->deviceModel = new UserDeviceModel();
        $this->ipsLogModel = new UserIpsLogModel();
        $this->jwt         = new JwtLibrary();
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
        $body = $this->request->getJSON(true) ?? [];

        // Validate required fields
        $required = ['email', 'password'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                return $this->error("Veld '{$field}' is verplicht.", 422);
            }
        }

        $email    = strtolower(trim($body['email']));
        $password = $body['password'];
        $deviceId = $body['device_id'] ?? null;

        // Validate email format
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Ongeldig e-mailadres.', 422);
        }

        // Check minimum password length
        if (strlen($password) < 8) {
            return $this->error('Wachtwoord moet minimaal 8 tekens bevatten.', 422);
        }

        // Check if email is already registered
        if ($this->userModel->findByEmail($email)) {
            return $this->error('Dit e-mailadres is al in gebruik.', 409);
        }

        // Create user — password gets hashed via model callback
        $userId = $this->userModel->insert([
            'email'    => $email,
            'password' => $password,
            'premium'  => 0,
        ]);

        if (! $userId) {
            return $this->error('Registratie mislukt. Probeer opnieuw.', 500);
        }

        $user = $this->userModel->find($userId);
        $ip   = $this->request->getIPAddress();

        // Log IP
        $this->ipsLogModel->log(
            (int) $userId,
            $ip,
            $this->request->getUserAgent()->getAgentString()
        );

        // Generate JWT token
        $token = $this->jwt->generate((int) $userId, false);

        return $this->respond([
            'success' => true,
            'message' => 'Registratie geslaagd.',
            'data'    => [
                'token'         => $token,
                'user_id'       => $userId,
                'email'         => $email,
                'premium'       => false,
                'premium_until' => null,
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
        $body = $this->request->getJSON(true) ?? [];

        $email    = strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';
        $deviceId = $body['device_id'] ?? null;

        if (empty($email) || empty($password)) {
            return $this->error('E-mail en wachtwoord zijn verplicht.', 422);
        }

        // Find user
        $user = $this->userModel->findByEmail($email);

        // Always run password_verify to prevent timing attacks
        if (! $user || ! $this->userModel->verifyPassword($password, $user['password'])) {
            return $this->error('Ongeldige inloggegevens.', 401);
        }

        // Check account active
        if (! $user['is_active']) {
            return $this->error('Dit account is geblokkeerd.', 403);
        }

        $ip        = $this->request->getIPAddress();
        $userAgent = $this->request->getUserAgent()->getAgentString();
        $isPremium = $this->userModel->isPremium($user);

        // Update login metadata
        $this->userModel->recordLogin((int) $user['id'], $ip);

        // Log IP + user agent
        $this->ipsLogModel->log((int) $user['id'], $ip, $userAgent);

        // Generate JWT
        $token = $this->jwt->generate((int) $user['id'], $isPremium);

        return $this->ok([
            'token'         => $token,
            'user_id'       => (int) $user['id'],
            'email'         => $user['email'],
            'premium'       => $isPremium,
            'premium_until' => $user['premium_until'],
        ], 'Inloggen geslaagd.');
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
        // For full token invalidation you would implement a token blacklist table.
        // Current implementation is stateless — client discards the token.
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
            'premium'        => $isPremium,
            'premium_until'  => $user['premium_until'],
            'created_at'     => $user['created_at'],
            'last_login_at'  => $user['last_login_at'],
            'xtream_server'  => $user['xtream_server'],
            'xtream_username'=> $user['xtream_username'],
            'xtream_password'=> $user['xtream_password'],
        ]);
    }
}
