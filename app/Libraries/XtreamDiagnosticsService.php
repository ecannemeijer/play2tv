<?php

declare(strict_types=1);

namespace App\Libraries;

class XtreamDiagnosticsService
{
    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function run(array $user): array
    {
        $startedAt = microtime(true);
        $checks = [];
        $summary = 'ok';

        if (empty($user['xtream_server']) || empty($user['xtream_username']) || empty($user['xtream_password'])) {
            return [
                'summary' => 'error',
                'duration_ms' => 0,
                'checks' => [[
                    'label' => 'Xtream configuratie',
                    'status' => 'error',
                    'message' => 'Xtream server, username of wachtwoord ontbreekt voor deze gebruiker.',
                ]],
            ];
        }

        $baseUrl = rtrim((string) $user['xtream_server'], '/');

        try {
            $accountInfo = $this->request($user, []);
            $checks[] = [
                'label' => 'Account handshake',
                'status' => 'ok',
                'message' => 'Xtream server bereikbaar en account antwoordt geldig.',
                'meta' => [
                    'server' => $baseUrl,
                    'username' => (string) $user['xtream_username'],
                    'auth' => isset($accountInfo['user_info']) ? 'user_info ontvangen' : 'player_api antwoord ontvangen',
                ],
            ];
        } catch (\Throwable $exception) {
            return [
                'summary' => 'error',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'checks' => [[
                    'label' => 'Account handshake',
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                    'meta' => [
                        'server' => $baseUrl,
                        'username' => (string) $user['xtream_username'],
                    ],
                ]],
            ];
        }

        foreach ([
            'live' => 'get_live_categories',
            'vod' => 'get_vod_categories',
            'series' => 'get_series_categories',
        ] as $label => $action) {
            try {
                $rows = $this->request($user, ['action' => $action]);
                $checks[] = [
                    'label' => strtoupper($label) . ' categories',
                    'status' => 'ok',
                    'message' => 'Categorie-opvraag succesvol.',
                    'meta' => [
                        'count' => is_array($rows) ? count($rows) : 0,
                    ],
                ];
            } catch (\Throwable $exception) {
                $summary = 'warning';
                $checks[] = [
                    'label' => strtoupper($label) . ' categories',
                    'status' => 'warning',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'summary' => $summary,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'checks' => $checks,
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, string> $query
     * @return array<mixed>
     */
    private function request(array $user, array $query): array
    {
        $url = rtrim((string) $user['xtream_server'], '/') . '/player_api.php?' . http_build_query([
            'username' => (string) $user['xtream_username'],
            'password' => (string) $user['xtream_password'],
            ...$query,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: Play2TV-XtreamDiagnostics/1.0\r\n",
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
            throw new \RuntimeException('Xtream bron antwoordde met HTTP ' . $statusCode . '.');
        }

        $payload = json_decode($body, true);
        if (! is_array($payload)) {
            throw new \RuntimeException('Xtream bron retourneerde ongeldige JSON data.');
        }

        if (isset($payload['user_info']['auth']) && (string) $payload['user_info']['auth'] !== '1') {
            throw new \RuntimeException('Xtream credentials zijn ongeldig of geweigerd door de provider.');
        }

        return $payload;
    }
}