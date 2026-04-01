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
                $directSource = trim((string) ($row['direct_source'] ?? ''));
                $url = $this->buildStreamUrl($user, $type, $streamId, $extension, $directSource);

                return [
                    'id' => $streamId,
                    'stream_id' => $streamId,
                    'name' => (string) ($row['name'] ?? 'Unnamed'),
                    'logo' => (string) ($row['stream_icon'] ?? $row['cover'] ?? ''),
                    'type' => $type,
                    'url' => $url,
                    'playback_mode' => $this->resolvePlaybackMode($type, $url, $extension),
                    'extension' => $extension,
                ];
            }, array_filter($rows, 'is_array'))),
        ]);
    }

    public function livePlaylist()
    {
        $streamId = trim((string) ($this->request->getGet('stream_id') ?? ''));

        if ($streamId === '') {
            return $this->error('Query stream_id is verplicht.', 422);
        }

        $user = $this->getXtreamUser();
        if (! is_array($user)) {
            return $user;
        }

        $sourceUrl = $this->buildStreamUrl($user, 'live', $streamId, 'm3u8');
        if (! $sourceUrl) {
            return $this->error('Kon live stream URL niet bepalen.', 422);
        }

        try {
            $result = $this->httpGet($sourceUrl, 'application/vnd.apple.mpegurl, application/x-mpegURL, */*');
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 502);
        }

        $manifest = $this->rewritePlaylistBody($result['body'], $sourceUrl);

        return $this->response
            ->setStatusCode(200)
            ->setContentType('application/vnd.apple.mpegurl')
            ->setBody($manifest);
    }

    public function media()
    {
        $target = trim((string) ($this->request->getGet('target') ?? ''));
        if ($target === '') {
            return $this->error('Query target is verplicht.', 422);
        }

        $decoded = base64_decode(strtr($target, '-_', '+/'), true);
        if ($decoded === false || $decoded === '') {
            return $this->error('Ongeldig media target.', 422);
        }

        $user = $this->getXtreamUser();
        if (! is_array($user)) {
            return $user;
        }

        $baseUrl = rtrim((string) $user['xtream_server'], '/');
        if (! str_starts_with($decoded, $baseUrl)) {
            return $this->error('Media target valt buiten de toegestane Xtream host.', 403);
        }

        try {
            $result = $this->httpGet($decoded, '*/*');
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 502);
        }

        return $this->response
            ->setStatusCode($result['status'])
            ->setContentType($result['contentType'] !== '' ? $result['contentType'] : 'application/octet-stream')
            ->setBody($result['body']);
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

    /**
     * @return array{status:int, body:string, contentType:string}
     */
    private function httpGet(string $url, string $accept): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => "Accept: {$accept}\r\nUser-Agent: Play2TV-XtreamProxy/1.0\r\n",
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
        $contentType = '';
        foreach ($http_response_header ?? [] as $header) {
            if ($contentType === '' && stripos($header, 'Content-Type:') === 0) {
                $contentType = trim(substr($header, strlen('Content-Type:')));
            }

            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches) === 1) {
                $statusCode = (int) $matches[1];
            }
        }

        if ($statusCode >= 400) {
            throw new \RuntimeException('Xtream bron antwoordde niet succesvol.');
        }

        return [
            'status' => $statusCode,
            'body' => $body,
            'contentType' => $contentType,
        ];
    }

    private function rewritePlaylistBody(string $body, string $playlistUrl): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];
        $rewritten = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                $rewritten[] = $line;
                continue;
            }

            $absolute = $this->resolvePlaylistUrl($playlistUrl, $trimmed);
            $rewritten[] = 'media?target=' . $this->encodeTarget($absolute);
        }

        return implode("\n", $rewritten);
    }

    private function resolvePlaylistUrl(string $base, string $candidate): string
    {
        if (preg_match('#^https?://#i', $candidate) === 1) {
            return $candidate;
        }

        if (str_starts_with($candidate, '/')) {
            $parts = parse_url($base);
            $scheme = $parts['scheme'] ?? 'http';
            $host = $parts['host'] ?? '';
            $port = isset($parts['port']) ? ':' . $parts['port'] : '';
            return $scheme . '://' . $host . $port . $candidate;
        }

        return rtrim(dirname($base), '/') . '/' . ltrim($candidate, '/');
    }

    private function encodeTarget(string $url): string
    {
        return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }

    private function buildStreamUrl(array $user, string $type, string $streamId, string $extension, string $directSource = ''): ?string
    {
        if ($directSource !== '') {
            return $directSource;
        }

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

    private function resolvePlaybackMode(string $type, ?string $url, string $extension): string
    {
        if ($type === 'series') {
            return 'unsupported';
        }

        $target = strtolower((string) ($url ?? ''));
        if (str_contains($target, '.m3u8')) {
            return 'hls';
        }

        if ($type === 'live') {
            return 'hls';
        }

        return in_array(strtolower($extension), ['mp4', 'mkv', 'mov', 'webm', 'm4v'], true)
            ? 'file'
            : 'file';
    }
}