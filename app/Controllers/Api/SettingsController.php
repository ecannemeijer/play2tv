<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\UserSettingsModel;

/**
 * SettingsController
 *
 * Stores and retrieves user app settings as JSON.
 *
 * Endpoints:
 *   POST /api/settings  → Save settings (JWT required)
 *   GET  /api/settings  → Get settings (JWT required)
 *
 * Android example:
 *   @POST("api/settings")
 *   suspend fun saveSettings(
 *     @Header("Authorization") token: String,
 *     @Body settings: Map<String, Any>
 *   ): Response<ApiResponse>
 *
 *   @GET("api/settings")
 *   suspend fun getSettings(
 *     @Header("Authorization") token: String
 *   ): Response<SettingsResponse>
 *
 * Settings JSON example (from Android SettingsViewModel):
 *   {
 *     "hardware_acceleration": true,
 *     "pin": "1234",
 *     "disabled_groups": ["News", "Kids"],
 *     "last_playlist_id": "abc-uuid-123"
 *   }
 */
class SettingsController extends BaseApiController
{
    private UserSettingsModel $settingsModel;

    public function __construct()
    {
        $this->settingsModel = new UserSettingsModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/settings
    // Header: Authorization: Bearer {token}
    // Body: any valid JSON object (settings key-value pairs)
    //
    // Response 200:
    //   { "success": true, "message": "Instellingen opgeslagen." }
    // ─────────────────────────────────────────────────────────────────────────
    public function save()
    {
        $userId   = $this->getAuthUserId();
        $settings = $this->getJsonBody();

        if ($settings === false) {
            return $this->error('Ongeldige instellingenpayload.', 422, $this->getValidationErrors());
        }

        if ($settings === []) {
            return $this->error('Geen instellingen ontvangen.', 422);
        }

        $clean = [];
        foreach ($settings as $key => $value) {
            $safeKey = preg_replace('/[^a-z0-9_\-]/i', '', (string) $key) ?? '';

            if ($safeKey === '') {
                continue;
            }

            $clean[$safeKey] = is_string($value)
                ? $this->sanitizeText($value, 1000)
                : $value;
        }

        if ($clean === []) {
            return $this->error('Geen geldige instellingen ontvangen.', 422);
        }

        $this->settingsModel->saveSettings($userId, $clean);

        return $this->ok([], 'Instellingen opgeslagen.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/settings
    // Header: Authorization: Bearer {token}
    //
    // Response 200:
    //   {
    //     "success": true,
    //     "data": {
    //       "hardware_acceleration": true,
    //       "pin": "1234",
    //       ...
    //     }
    //   }
    // ─────────────────────────────────────────────────────────────────────────
    public function get()
    {
        $userId   = $this->getAuthUserId();
        $settings = $this->settingsModel->getSettings($userId);

        return $this->ok($settings);
    }
}
