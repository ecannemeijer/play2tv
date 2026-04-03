<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\ApiCacheService;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class XtreamContentController extends BaseApiController
{
    private const CAST_TOKEN_TTL = 900;

    private UserModel $userModel;
    private ApiCacheService $apiCache;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->apiCache = new ApiCacheService();
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
            $rows = $this->apiCache->rememberXtreamCategories(
                $user,
                $type,
                fn (): array => $this->xtreamRequest($user, $action)
            );
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
            $rows = $this->apiCache->rememberXtreamChannels(
                $user,
                $type,
                $categoryId,
                fn (): array => $this->xtreamRequest($user, $action, $query)
            );
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 502);
        }

        return $this->ok([
            'items' => array_values(array_map(function (array $row) use ($type, $user): array {
                $streamId = (string) ($row['stream_id'] ?? $row['series_id'] ?? '');
                $extension = (string) ($row['container_extension'] ?? 'mp4');
                $directSource = trim((string) ($row['direct_source'] ?? ''));
                $url = $this->buildStreamUrl($user, $type, $streamId, $extension, $directSource);
                $userId = (int) ($user['id'] ?? 0);
                $categoryIds = $this->extractCategoryIds($row);
                $primaryCategoryId = $categoryIds[0] ?? '';

                return [
                    'id' => $streamId,
                    'stream_id' => $streamId,
                    'name' => (string) ($row['name'] ?? 'Unnamed'),
                    'logo' => (string) ($row['stream_icon'] ?? $row['cover'] ?? ''),
                    'type' => $type,
                    'category_id' => $primaryCategoryId,
                    'category_ids' => $categoryIds,
                    'category' => (string) ($row['category_name'] ?? $row['genre'] ?? ''),
                    'url' => $url,
                    'cast_url' => $this->buildCastUrl($type, $userId, $streamId, $url),
                    'playback_mode' => $this->resolvePlaybackMode($type, $url, $extension),
                    'extension' => $extension,
                ];
            }, array_filter($rows, 'is_array'))),
        ]);
    }

    public function castLivePlaylist()
    {
        $userId = (int) ($this->request->getGet('user_id') ?? 0);
        $streamId = trim((string) ($this->request->getGet('stream_id') ?? ''));
        $expires = (int) ($this->request->getGet('expires') ?? 0);
        $signature = trim((string) ($this->request->getGet('signature') ?? ''));

        $this->logCastDebug('cast_live_playlist_request', [
            'user_id' => $userId,
            'stream_id' => $streamId,
            'expires' => $expires,
            'ip' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
        ]);

        if ($userId <= 0 || $streamId === '' || $expires <= 0 || $signature === '') {
            $this->logCastDebug('cast_live_playlist_invalid_request', [
                'user_id' => $userId,
                'stream_id' => $streamId,
                'expires' => $expires,
            ]);
            return $this->error('Ongeldige cast playlist aanvraag.', 422);
        }

        if (! $this->isValidCastSignature('live-playlist', [
            'user_id' => (string) $userId,
            'stream_id' => $streamId,
            'expires' => (string) $expires,
        ], $signature)) {
            $this->logCastDebug('cast_live_playlist_invalid_signature', [
                'user_id' => $userId,
                'stream_id' => $streamId,
                'expires' => $expires,
            ]);
            return $this->error('Cast playlist handtekening ongeldig.', 403);
        }

        if ($expires < time()) {
            $this->logCastDebug('cast_live_playlist_expired', [
                'user_id' => $userId,
                'stream_id' => $streamId,
                'expires' => $expires,
            ]);
            return $this->error('Cast playlist token verlopen.', 403);
        }

        $user = $this->userModel->find($userId);
        if (! $user) {
            return $this->error('Gebruiker niet gevonden.', 404);
        }

        $sourceUrl = $this->buildStreamUrl($user, 'live', $streamId, 'm3u8');
        if (! $sourceUrl) {
            return $this->error('Kon live stream URL niet bepalen.', 422);
        }

        try {
            $result = $this->httpGet($sourceUrl, 'application/vnd.apple.mpegurl, application/x-mpegURL, */*');
        } catch (\Throwable $exception) {
            $this->logCastDebug('cast_live_playlist_upstream_error', [
                'user_id' => $userId,
                'stream_id' => $streamId,
                'message' => $exception->getMessage(),
            ]);
            return $this->error($exception->getMessage(), 502);
        }

        $this->logCastDebug('cast_live_playlist_success', [
            'user_id' => $userId,
            'stream_id' => $streamId,
            'final_url' => $result['finalUrl'],
        ]);

        $manifest = $this->rewriteCastPlaylistBody($result['body'], $result['finalUrl'], $userId, $expires);

        return $this->response
            ->setStatusCode(200)
            ->setContentType('application/vnd.apple.mpegurl')
            ->setBody($manifest);
    }

    public function castMedia()
    {
        $userId = (int) ($this->request->getGet('user_id') ?? 0);
        $expires = (int) ($this->request->getGet('expires') ?? 0);
        $target = trim((string) ($this->request->getGet('target') ?? ''));
        $signature = trim((string) ($this->request->getGet('signature') ?? ''));

        $this->logCastDebug('cast_media_request', [
            'user_id' => $userId,
            'expires' => $expires,
            'target' => substr($target, 0, 80),
            'range' => $this->request->getHeaderLine('Range'),
            'ip' => $this->request->getIPAddress(),
        ]);

        if ($userId <= 0 || $expires <= 0 || $target === '' || $signature === '') {
            $this->logCastDebug('cast_media_invalid_request', [
                'user_id' => $userId,
                'expires' => $expires,
                'target' => substr($target, 0, 80),
            ]);
            return $this->error('Ongeldige cast media aanvraag.', 422);
        }

        if (! $this->isValidCastSignature('media', [
            'user_id' => (string) $userId,
            'target' => $target,
            'expires' => (string) $expires,
        ], $signature)) {
            $this->logCastDebug('cast_media_invalid_signature', [
                'user_id' => $userId,
                'expires' => $expires,
            ]);
            return $this->error('Cast media handtekening ongeldig.', 403);
        }

        if ($expires < time()) {
            $this->logCastDebug('cast_media_expired', [
                'user_id' => $userId,
                'expires' => $expires,
            ]);
            return $this->error('Cast media token verlopen.', 403);
        }

        $decoded = base64_decode(strtr($target, '-_', '+/'), true);
        if ($decoded === false || $decoded === '') {
            return $this->error('Ongeldig media target.', 422);
        }

        if (preg_match('#^https?://#i', $decoded) !== 1) {
            return $this->error('Media target moet een geldige http(s) URL zijn.', 422);
        }

        $rangeHeader = trim((string) ($this->request->getHeaderLine('Range') ?? ''));
        if ($rangeHeader === '' && preg_match('/\.ts($|\?)/i', $decoded) === 1) {
            $rangeHeader = 'bytes=0-';
        }

        try {
            $result = $this->httpGet($decoded, '*/*', $rangeHeader);
        } catch (\Throwable $exception) {
            $this->logCastDebug('cast_media_upstream_error', [
                'user_id' => $userId,
                'message' => $exception->getMessage(),
                'target_url' => $decoded,
            ]);
            return $this->error($exception->getMessage(), 502);
        }

        $this->logCastDebug('cast_media_success', [
            'user_id' => $userId,
            'status' => $result['status'],
            'content_type' => $result['contentType'],
            'target_url' => $decoded,
        ]);

        return $this->response
            ->setStatusCode($result['status'])
            ->setContentType($result['contentType'] !== '' ? $result['contentType'] : 'application/octet-stream')
            ->setBody($result['body']);
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

        $manifest = $this->rewritePlaylistBody($result['body'], $result['finalUrl']);

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

        if (preg_match('#^https?://#i', $decoded) !== 1) {
            return $this->error('Media target moet een geldige http(s) URL zijn.', 422);
        }

        $rangeHeader = trim((string) ($this->request->getHeaderLine('Range') ?? ''));
        if ($rangeHeader === '' && preg_match('/\.ts($|\?)/i', $decoded) === 1) {
            $rangeHeader = 'bytes=0-';
        }

        try {
            $result = $this->httpGet($decoded, '*/*', $rangeHeader);
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 502);
        }

        return $this->response
            ->setStatusCode($result['status'])
            ->setContentType($result['contentType'] !== '' ? $result['contentType'] : 'application/octet-stream')
            ->setBody($result['body']);
    }

    private function getXtreamUser(): array|ResponseInterface
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
    * @return array{status:int, body:string, contentType:string, finalUrl:string}
     */
    private function httpGet(string $url, string $accept, string $rangeHeader = ''): array
    {
        $headers = "Accept: {$accept}\r\nUser-Agent: Play2TV-XtreamProxy/1.0\r\nConnection: close\r\n";
        if ($rangeHeader !== '') {
            $headers .= "Range: {$rangeHeader}\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => $headers,
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
        $finalUrl = $url;
        foreach ($http_response_header ?? [] as $header) {
            if ($contentType === '' && stripos($header, 'Content-Type:') === 0) {
                $contentType = trim(substr($header, strlen('Content-Type:')));
            }

            if (stripos($header, 'Location:') === 0) {
                $location = trim(substr($header, strlen('Location:')));
                if ($location !== '') {
                    $finalUrl = $this->resolvePlaylistUrl($finalUrl, $location);
                }
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
            'finalUrl' => $finalUrl,
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

    private function rewriteCastPlaylistBody(string $body, string $playlistUrl, int $userId, int $expires): string
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
            $encodedTarget = $this->encodeTarget($absolute);
            $params = [
                'user_id' => (string) $userId,
                'target' => $encodedTarget,
                'expires' => (string) $expires,
            ];
            $params['signature'] = $this->signCastRequest('media', $params);

            $rewritten[] = 'media?' . http_build_query($params);
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

    private function buildCastUrl(string $type, int $userId, string $streamId, ?string $fallbackUrl): ?string
    {
        if ($type !== 'live' || $userId <= 0 || $streamId === '') {
            return $fallbackUrl;
        }

        try {
            $this->getCastSigningKey();
        } catch (\Throwable) {
            return $fallbackUrl;
        }

        $expires = time() + self::CAST_TOKEN_TTL;
        $params = [
            'user_id' => (string) $userId,
            'stream_id' => $streamId,
            'expires' => (string) $expires,
        ];
        $params['signature'] = $this->signCastRequest('live-playlist', $params);

        return '/api/content/cast/live-playlist?' . http_build_query($params);
    }

    private function signCastRequest(string $scope, array $params): string
    {
        $payload = [$scope];
        foreach ($params as $key => $value) {
            $payload[] = $key . '=' . $value;
        }

        return hash_hmac('sha256', implode('|', $payload), $this->getCastSigningKey());
    }

    private function isValidCastSignature(string $scope, array $params, string $signature): bool
    {
        return hash_equals($this->signCastRequest($scope, $params), $signature);
    }

    private function getCastSigningKey(): string
    {
        $encryptionConfig = new \Config\Encryption();
        $key = is_string($encryptionConfig->key) ? trim($encryptionConfig->key) : '';

        if ($key === '') {
            $envKey = trim((string) (getenv('encryption.key') ?: ''));
            if ($envKey !== '') {
                $key = $envKey;
            }
        }

        if ($key === '') {
            throw new \RuntimeException('encryption.key moet zijn ingesteld voor cast ondersteuning.');
        }

        return $key;
    }

    private function logCastDebug(string $event, array $context = []): void
    {
        try {
            \Config\Services::logger()->error('cast_debug ' . $event . ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } catch (\Throwable) {
        }
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

    /**
     * @return list<string>
     */
    private function extractCategoryIds(array $row): array
    {
        $rawValue = $row['category_id'] ?? $row['category_ids'] ?? [];

        if (is_array($rawValue)) {
            return array_values(array_filter(array_map(
                static fn ($value): string => trim((string) $value),
                $rawValue,
            ), static fn (string $value): bool => $value !== ''));
        }

        $text = trim((string) $rawValue);
        if ($text === '') {
            return [];
        }

        if (str_starts_with($text, '[') && str_ends_with($text, ']')) {
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map(
                    static fn ($value): string => trim((string) $value),
                    $decoded,
                ), static fn (string $value): bool => $value !== ''));
            }
        }

        if (str_contains($text, ',')) {
            return array_values(array_filter(array_map(
                static fn (string $value): string => trim($value),
                explode(',', $text),
            ), static fn (string $value): bool => $value !== ''));
        }

        return [$text];
    }
}