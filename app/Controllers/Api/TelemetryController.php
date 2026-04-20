<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\DeviceFingerprintService;
use App\Libraries\SecurityEventService;
use App\Models\TelemetryEventModel;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;

class TelemetryController extends BaseApiController
{
    private const MAX_SANITIZE_DEPTH = 4;
    private const MAX_ARRAY_ITEMS = 25;
    private const MAX_STRING_LENGTH = 500;

    private TelemetryEventModel $telemetryEvents;
    private DeviceFingerprintService $fingerprints;
    private SecurityEventService $securityEvents;

    public function __construct()
    {
        $this->telemetryEvents = new TelemetryEventModel();
        $this->fingerprints = new DeviceFingerprintService();
        $this->securityEvents = new SecurityEventService();
    }

    public function store()
    {
        try {
            if (($rateLimitResponse = $this->guardRateLimit()) !== null) {
                return $rateLimitResponse;
            }

            $rawBody = (string) $this->request->getBody();
            $maxPayloadBytes = max(4096, (int) env('telemetry.maxPayloadBytes', 131072));
            if ($rawBody !== '' && strlen($rawBody) > $maxPayloadBytes) {
                return $this->error('Telemetry payload is te groot.', 413);
            }

            $payload = $this->getJsonBody(['type', 'timestamp', 'data']);
            if ($payload === false) {
                return $this->error('Ongeldige telemetry payload.', 422, $this->getValidationErrors());
            }

            if (! is_array($payload['data'])) {
                return $this->error('Telemetry data moet een object zijn.', 422, ['data' => 'Telemetry data moet een object zijn.']);
            }

            $normalizedType = $this->normalizeEventType((string) $payload['type']);
            if ($normalizedType === '') {
                return $this->error('Telemetry type ontbreekt of is ongeldig.', 422, ['type' => 'Gebruik alleen letters, cijfers en underscores.']);
            }

            $sanitizedData = $this->sanitizeValue($payload['data']);
            if (! is_array($sanitizedData)) {
                $sanitizedData = [];
            }

            $record = [
                'event_type' => $normalizedType,
                'severity' => $this->deriveSeverity($normalizedType),
                'app_version' => $this->sanitizeScalarText($payload['app_version'] ?? null, 50),
                'app_code' => $this->sanitizePositiveInt($payload['app_code'] ?? null),
                'device_name' => $this->sanitizeScalarText($payload['device'] ?? null, 120),
                'android_version' => $this->sanitizeScalarText($payload['android_version'] ?? null, 20),
                'channel_name' => $this->sanitizeScalarText($sanitizedData['channel_name'] ?? null, 255),
                'last_action' => $this->sanitizeScalarText($sanitizedData['last_action'] ?? null, 80),
                'stream_type' => $this->sanitizeScalarText($sanitizedData['streamType'] ?? ($sanitizedData['stream_type'] ?? null), 40),
                'client_timestamp' => $this->normalizeTimestamp($payload['timestamp']),
                'ip_hash' => $this->fingerprints->buildIpHash((string) $this->request->getIPAddress()),
                'fingerprint_hash' => $this->fingerprints->buildFingerprintHash(
                    (string) $this->request->getUserAgent()->getAgentString(),
                    (string) $this->request->getIPAddress(),
                    $this->sanitizeScalarText($payload['device'] ?? null, 120)
                ),
                'data_json' => json_encode($sanitizedData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_at' => gmdate('Y-m-d H:i:s'),
            ];

            $inserted = $this->telemetryEvents->insert($record, false);
            if ($inserted === false) {
                $dbError = $this->telemetryEvents->db->error();
                $modelErrors = $this->telemetryEvents->errors();

                log_message('error', 'Telemetry insert failed. DB error: {dbError}; Model errors: {modelErrors}; Record: {record}', [
                    'dbError' => json_encode($dbError, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'modelErrors' => json_encode($modelErrors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'record' => json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]);

                throw new RuntimeException('Telemetry insert failed.');
            }

            return $this->created([
                'id' => (int) $this->telemetryEvents->getInsertID(),
                'received_at' => $record['created_at'],
            ], 'Telemetry ontvangen.');
        } catch (Throwable $exception) {
            log_message('error', 'Telemetry ingest failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return $this->error('Telemetry kon niet worden verwerkt.', 500);
        }
    }

    private function guardRateLimit(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        $maxRequests = max(30, (int) env('telemetry.maxRequestsPerMinute', 240));
        $bucket = sprintf(
            'telemetry_rate_%s_%s',
            sha1((string) $this->request->getIPAddress()),
            gmdate('YmdHi')
        );

        try {
            $cache = cache();
            $count = (int) ($cache->get($bucket) ?? 0);

            if ($count >= $maxRequests) {
                $this->securityEvents->log('telemetry_rate_limited', 'warning', $this->request, null, [
                    'route' => 'telemetry',
                ]);

                return $this->rateLimitError('Telemetry rate limit bereikt.', 60);
            }

            $cache->save($bucket, $count + 1, 60);
        } catch (Throwable $exception) {
            log_message('warning', 'Telemetry rate limit cache unavailable: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    private function normalizeEventType(string $type): string
    {
        $normalized = strtolower(trim($type));
        $normalized = preg_replace('/[^a-z0-9_]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        return substr($normalized, 0, 80);
    }

    private function deriveSeverity(string $type): string
    {
        if (str_contains($type, 'crash') || str_contains($type, 'error')) {
            return 'error';
        }

        if (str_contains($type, 'rebuffer') || str_contains($type, 'timeout') || str_contains($type, 'failed')) {
            return 'warning';
        }

        return 'info';
    }

    private function normalizeTimestamp(mixed $value): ?string
    {
        if (is_string($value) && trim($value) === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $timestamp = (int) $value;
        if ($timestamp > 9999999999) {
            $timestamp = (int) floor($timestamp / 1000);
        }

        if ($timestamp <= 0) {
            return null;
        }

        return (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }

    private function sanitizePositiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized >= 0 ? $normalized : null;
    }

    private function sanitizeScalarText(mixed $value, int $maxLength): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        $normalized = $this->sanitizeText((string) $value, $maxLength);

        return $normalized !== '' ? $this->sanitizeSensitiveText($normalized, $maxLength) : null;
    }

    private function sanitizeValue(mixed $value, int $depth = 0): mixed
    {
        if ($depth >= self::MAX_SANITIZE_DEPTH) {
            return '[truncated]';
        }

        if (is_array($value)) {
            $sanitized = [];
            $count = 0;

            foreach ($value as $key => $child) {
                if ($count >= self::MAX_ARRAY_ITEMS) {
                    $sanitized['_truncated'] = true;
                    break;
                }

                $sanitizedKey = is_string($key)
                    ? substr(preg_replace('/[^a-z0-9_:-]+/i', '_', strtolower($key)) ?? 'key', 0, 60)
                    : (string) $key;

                if ($sanitizedKey === '') {
                    $sanitizedKey = 'key_' . $count;
                }

                if ($sanitizedKey === 'recent_logs' && is_array($child)) {
                    $sanitized[$sanitizedKey] = array_map(
                        fn ($line) => $this->sanitizeSensitiveText((string) $line, self::MAX_STRING_LENGTH),
                        array_slice(array_values($child), 0, 10)
                    );
                } else {
                    $sanitized[$sanitizedKey] = $this->sanitizeValue($child, $depth + 1);
                }

                $count++;
            }

            return $sanitized;
        }

        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        return $this->sanitizeSensitiveText((string) $value, self::MAX_STRING_LENGTH);
    }

    private function sanitizeSensitiveText(string $value, int $maxLength): string
    {
        $clean = $this->sanitizeText($value, max($maxLength * 4, $maxLength));

        $clean = preg_replace('/([?&](?:username|password|token|access_token|refresh_token|api_key|authorization)=)[^&\s]+/i', '$1[redacted]', $clean) ?? $clean;
        $clean = preg_replace('/(https?:\/\/)([^\/@\s]+)@/i', '$1[redacted]@', $clean) ?? $clean;
        $clean = preg_replace('/\b(username|password|token|access_token|refresh_token|authorization|api_key)\s*[:=]\s*[^,\s]+/i', '$1=[redacted]', $clean) ?? $clean;

        if (strlen($clean) > $maxLength) {
            $clean = substr($clean, 0, $maxLength);
        }

        return $clean;
    }
}