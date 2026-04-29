<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\UserDeviceModel;
use App\Models\UserModel;

class UserBootstrapBuilder
{
    private UserModel $userModel;
    private UserDeviceModel $deviceModel;
    private TelemetryConfigProvider $telemetryConfig;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->deviceModel = new UserDeviceModel();
        $this->telemetryConfig = new TelemetryConfigProvider();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildForUser(array $user, ?string $currentDeviceId = null, ?string $ipAddress = null): array
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            throw new \InvalidArgumentException('User ID ontbreekt voor bootstrap.');
        }

        if ($currentDeviceId !== null && trim($currentDeviceId) !== '') {
            $this->deviceModel->touchDevice($userId, trim($currentDeviceId), null, $ipAddress);
        }

        return [
            'config' => $this->telemetryConfig->getConfig(),
            'profile' => $this->buildProfile($user),
            'devices' => [
                'user_id' => $userId,
                'max_devices' => UserDeviceModel::MAX_DEVICES,
                'devices' => $this->deviceModel->getDevicesForUser($userId),
            ],
            'bootstrapped_at' => date(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProfile(array $user): array
    {
        $isPremium = $this->userModel->isPremium($user);

        return [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'premium' => $isPremium,
            'premium_until' => $user['premium_until'],
            'created_at' => $user['created_at'],
            'last_login_at' => $user['last_login_at'],
            'xtream_server' => $user['xtream_server'],
            'xtream_username' => $user['xtream_username'],
            'xtream_password' => $user['xtream_password'],
        ];
    }
}