<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\UserModel;

class XtreamContentController extends BaseApiController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function categories()
    {
        $type = strtolower(trim((string) ($this->request->getGet('type') ?? '')));

        if (! in_array($type, ['live', 'vod', 'series'], true)) {
            return $this->error("Query 'type' moet live, vod of series zijn.", 422);
        }

        $user = $this->getXtreamUser();
        if (! is_array($user)) {
            return $user;
        }

        $action = match ($type) {
            'live' => 'get_live_categories',
            'vod' => 'get_vod_categories',
            default => 'get_series_categories',
        };

        try {
            $rows = $this->xtreamRequest($user, $action);
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 502);
        }

        return $this->ok([
            'items' => array_values(array_map(static function (array $row) use ($type): array {
                return [
                    'id' => (string) ($row['category_id'] ?? ''),
                    'name' => (string) ($row['category_name'] ?? 'Unknown'),
                    'type' => $type,
                ];
            }, array_filter($rows, 'is_array'))),
        ]);
    }

    public function channels()
    {
        $type = strtolower(trim((string) ($this->request->getGet('type') ?? '')));
        $categoryId = trim((string) ($this->request->getGet('category_id') ?? ''));

        if (! in_array($type, ['live', 'vod', 'series'], true)) {
            return $this->error("Query 'type' moet live, vod of series zijn.", 422);
        }

        $user = $this->getXtreamUser();
        if (! is_array($user)) {
            return $user;
        }

        $action = match ($type) {
            'live' => 'get_live_streams',
            'vod' => 'get_vod_streams',
            default => 'get_series',
        };

        $query = [];
        if ($categoryId !== '') {
            $query['category_id'] = $categoryId;
        }

        try {
            $rows = $this->xtreamRequest($user, $action, $query);
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 502);
        }

        return $this->ok([
            'items' => array_values(array_map(function (array $row) use ($type, $user): array {
                $streamId = (string) ($row['stream_id'] ?? $row['series_id'] ?? '');
                $extension = (string) ($row['container_extension'] ?? 'mp4');

                return [
                    'id' => $streamId,
                    'name' => (string) ($row['name'] ?? 'Unnamed'),
                    'logo' => (string) ($row['stream_icon'] ?? $row['cover'] ?? ''),
                    'type' => $type,
                    'url' => $this->buildStreamUrl($user, $type, $streamId, $extension),
                ];
            }, array_filter($rows, 'is_array'))),
        ]);
    }

    private function getXtreamUser(): array|\CodeIgniter\HTTP\ResponseInterface
    {
        $user = $this->userModel->find($this->getAuthUserId());

        if (! $user) {
            return $this->error('Gebruiker niet gevonden.', 404);
        }

        if (empty($user['xtream_server']) || empty($user['xtream_username']) || empty($user['xtream_password'])) {
            return $this->error('Xtream gegevens ontbreken voor dit account.', 422);
        }

        return $user;
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
                'header' => "Accept: application/json\r\nUser-Agent: Play2TV-XtreamProxy/1.0\r\n",
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

    private function buildStreamUrl(array $user, string $type, string $streamId, string $extension): ?string
    {
        if ($streamId === '') {
            return null;
        }

        $baseUrl = rtrim((string) $user['xtream_server'], '/');
        $username = rawurlencode((string) $user['xtream_username']);
        $password = rawurlencode((string) $user['xtream_password']);
        $id = rawurlencode($streamId);

        return match ($type) {
            'live' => $baseUrl . '/live/' . $username . '/' . $password . '/' . $id . '.m3u8',
            'vod' => $baseUrl . '/movie/' . $username . '/' . $password . '/' . $id . '.' . rawurlencode($extension !== '' ? $extension : 'mp4'),
            default => null,
        };
    }
}