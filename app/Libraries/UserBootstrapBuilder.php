<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\UserCategoryPrefModel;
use App\Models\UserDeviceModel;
use App\Models\UserModel;
use App\Models\UserSettingsModel;

class UserBootstrapBuilder
{
    private UserModel $userModel;
    private UserDeviceModel $deviceModel;
    private UserSettingsModel $settingsModel;
    private UserCategoryPrefModel $categoryPrefModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->deviceModel = new UserDeviceModel();
        $this->settingsModel = new UserSettingsModel();
        $this->categoryPrefModel = new UserCategoryPrefModel();
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
            'profile' => $this->buildProfile($user),
            'devices' => [
                'user_id' => $userId,
                'max_devices' => UserDeviceModel::MAX_DEVICES,
                'devices' => $this->deviceModel->getDevicesForUser($userId),
            ],
            'settings' => $this->settingsModel->getSettings($userId),
            'categoriesByType' => [
                'live' => $this->getXtreamCategories($user, 'live'),
                'vod' => $this->getXtreamCategories($user, 'vod'),
                'series' => $this->getXtreamCategories($user, 'series'),
            ],
            'categoryPrefsByType' => [
                'live' => $this->getCategoryPrefs($userId, 'live'),
                'vod' => $this->getCategoryPrefs($userId, 'vod'),
                'series' => $this->getCategoryPrefs($userId, 'series'),
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCategoryPrefs(int $userId, string $type): array
    {
        $rows = $this->categoryPrefModel->getByType($userId, $type);

        return array_map(static function (array $row): array {
            return [
                'name' => $row['category_key'],
                'display_name' => $row['display_name'],
                'visible' => (bool) $row['visible'],
                'position' => (int) $row['position'],
            ];
        }, $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getXtreamCategories(array $user, string $type): array
    {
        if (empty($user['xtream_server']) || empty($user['xtream_username']) || empty($user['xtream_password'])) {
            return [];
        }

        $action = match ($type) {
            'live' => 'get_live_categories',
            'vod' => 'get_vod_categories',
            default => 'get_series_categories',
        };

        try {
            $rows = $this->xtreamRequest($user, $action);
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_map(static function (array $row, int $index): array {
            $name = (string) ($row['category_name'] ?? 'Unknown');

            return [
                'id' => (string) ($row['category_id'] ?? ''),
                'name' => $name,
                'display_name' => $name,
                'visible' => true,
                'position' => $index * 10,
            ];
        }, array_values(array_filter($rows, 'is_array')), array_keys(array_values(array_filter($rows, 'is_array')))));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function xtreamRequest(array $user, string $action, array $query = []): array
    {
        $baseUrl = rtrim((string) $user['xtream_server'], '/');

        $url = $baseUrl . '/player_api.php?' . http_build_query([
            'username' => (string) $user['xtream_username'],
            'password' => (string) $user['xtream_password'],
            'action' => $action,
            ...$query,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: Play2TV-Bootstrap/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new \RuntimeException('Xtream bron kon niet worden bereikt.');
        }

        $statusCode = 200;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches) === 1) {
                $statusCode = (int) $matches[1];
                break;
            }
        }

        if ($statusCode >= 400) {
            throw new \RuntimeException('Xtream bron antwoordde niet succesvol.');
        }

        $payload = json_decode($body, true);
        if (! is_array($payload)) {
            throw new \RuntimeException('Xtream bron retourneerde ongeldige data.');
        }

        return $payload;
    }
}