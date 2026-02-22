<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserSettingsModel
 *
 * Stores application settings for each user as a JSON blob.
 *
 * Android API usage:
 *   POST /api/settings → Body: {"hardware_acceleration": true, "pin": "1234", ...}
 *   GET  /api/settings → Returns full settings JSON
 */
class UserSettingsModel extends Model
{
    protected $table      = 'user_settings';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'settings_json',
        'updated_at',
    ];

    protected $useTimestamps = false;

    /**
     * Save (upsert) settings for a user
     *
     * @param int   $userId
     * @param array $settings Associative array will be JSON-encoded
     */
    public function saveSettings(int $userId, array $settings): void
    {
        $existing = $this->where('user_id', $userId)->first();

        $data = [
            'user_id'       => $userId,
            'settings_json' => json_encode($settings, JSON_UNESCAPED_UNICODE),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }

    /**
     * Get decoded settings array for a user
     *
     * @return array<string, mixed>
     */
    public function getSettings(int $userId): array
    {
        $row = $this->where('user_id', $userId)->first();

        if (! $row || ! $row['settings_json']) {
            return [];
        }

        return json_decode($row['settings_json'], true) ?? [];
    }
}
