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
    public const MAX_DEVICES = 5;

    protected $table      = 'user_devices';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'device_id',
        'device_name',
        'last_used',
        'ip_address',
    ];

    protected $useTimestamps = false;

    /**
     * Register or update a device for a user
     */
    public function findByUserAndDevice(int $userId, string $deviceId): ?array
    {
        return $this->where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->first();
    }

    public function touchDevice(int $userId, string $deviceId, ?string $deviceName = null, ?string $ip = null): void
    {
        $existing = $this->findByUserAndDevice($userId, $deviceId);

        if (! $existing) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $payload = [
            'last_used' => $now,
        ];

        if ($deviceName !== null && trim($deviceName) !== '') {
            $payload['device_name'] = trim($deviceName);
        }

        if ($ip !== null && trim($ip) !== '') {
            $payload['ip_address'] = trim($ip);
        }

        $this->update((int) $existing['id'], $payload);
    }

    public function registerDevice(int $userId, string $deviceId, string $deviceName, ?string $ip = null): array
    {
        $existing = $this->findByUserAndDevice($userId, $deviceId);
        $now = date('Y-m-d H:i:s');

        $payload = [
            'user_id' => $userId,
            'device_id' => trim($deviceId),
            'device_name' => trim($deviceName),
            'last_used' => $now,
            'ip_address' => $ip,
        ];

        if ($existing) {
            $this->update((int) $existing['id'], $payload);
            return $this->find((int) $existing['id']) ?? $payload;
        } else {
            $deviceIdPk = $this->insert($payload, true);
            return $this->find((int) $deviceIdPk) ?? $payload;
        }
    }

    public function replaceDevice(int $userId, string $oldDeviceId, string $newDeviceId, string $deviceName, ?string $ip = null): array
    {
        $oldDevice = $this->findByUserAndDevice($userId, $oldDeviceId);

        if ($oldDevice) {
            $this->delete((int) $oldDevice['id']);
        }

        return $this->registerDevice($userId, $newDeviceId, $deviceName, $ip);
    }

    public function countDistinctDevicesForUser(int $userId): int
    {
        return $this->where('user_id', $userId)->countAllResults();
    }

    /**
     * Get all devices for a user (admin view)
     */
    public function getDevicesForUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('last_used', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}
