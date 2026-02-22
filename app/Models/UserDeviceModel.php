<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserDeviceModel
 *
 * Tracks which Android devices are linked to each user.
 * The Android app sends a device_id (e.g. Android ID) on login.
 *
 * Android API usage:
 *   POST /api/login → Body includes "device_id" field
 */
class UserDeviceModel extends Model
{
    protected $table      = 'user_devices';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'device_id',
        'last_seen',
        'ip_address',
    ];

    protected $useTimestamps = false;

    /**
     * Register or update a device for a user
     */
    public function upsertDevice(int $userId, string $deviceId, string $ip): void
    {
        $existing = $this->where('user_id', $userId)
                         ->where('device_id', $deviceId)
                         ->first();

        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $this->update($existing['id'], [
                'last_seen'  => $now,
                'ip_address' => $ip,
            ]);
        } else {
            $this->insert([
                'user_id'    => $userId,
                'device_id'  => $deviceId,
                'last_seen'  => $now,
                'ip_address' => $ip,
            ]);
        }
    }

    /**
     * Get all devices for a user (admin view)
     */
    public function getDevicesForUser(int $userId): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('last_seen', 'DESC')
                    ->findAll();
    }
}
