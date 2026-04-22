<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\DeviceFingerprintService;
use App\Libraries\SecurityEventService;
use App\Models\TelemetryEventModel;
use Config\App;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class TelemetryController extends BaseApiController
{
    private const MAX_SANITIZE_DEPTH = 4;
    private const MAX_ARRAY_ITEMS = 25;
    private const MAX_STRING_LENGTH = 500;
    private const DEFAULT_MAX_BATCH_EVENTS = 100;

    private TelemetryEventModel $telemetryEvents;
    private DeviceFingerprintService $fingerprints;
    private SecurityEventService $securityEvents;
    private DateTimeZone $appTimeZone;

    public function __construct()
    {
        $this->telemetryEvents = new TelemetryEventModel();
        $this->fingerprints = new DeviceFingerprintService();
        $this->securityEvents = new SecurityEventService();
        $this->appTimeZone = new DateTimeZone(config(App::class)->appTimezone);
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

            $payload = $this->getJsonBody();
            if ($payload === false) {
                return $this->error('Ongeldige telemetry payload.', 422, $this->getValidationErrors());
            }

            $records = [];
            foreach ($this->extractEvents($payload) as $eventPayload) {
                $records[] = $this->buildRecord($eventPayload);
            }

            $insertIds = [];
            $this->telemetryEvents->db->transStart();
            foreach ($records as $record) {
                $insertIds[] = $this->insertRecord($record);
            }
            $this->telemetryEvents->db->transComplete();

            if ($this->telemetryEvents->db->transStatus() === false) {
                throw new RuntimeException('Telemetry batch insert failed.');
            }

            if (count($insertIds) === 1) {
                return $this->created([
                    'id' => $insertIds[0],
                    'received_at' => $records[0]['created_at'],
                ], 'Telemetry ontvangen.');
            }

            return $this->created([
                'received_count' => count($insertIds),
                'received_at' => $records[0]['created_at'],
            ], 'Telemetry batch ontvangen.');
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        } catch (Throwable $exception) {
            log_message('error', 'Telemetry ingest failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return $this->error('Telemetry kon niet worden verwerkt.', 500);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<array<string, mixed>>
     */
    private function extractEvents(array $payload): array
    {
        if (! array_key_exists('events', $payload)) {
            return [$payload];
        }

        if (! is_array($payload['events'])) {
            throw new InvalidArgumentException('Telemetry events moet een array zijn.');
        }

        $events = array_values($payload['events']);
        if ($events === []) {
            throw new InvalidArgumentException('Telemetry batch mag niet leeg zijn.');
        }

        $maxBatchEvents = max(1, (int) env('telemetry.maxEventsPerBatch', self::DEFAULT_MAX_BATCH_EVENTS));
        if (count($events) > $maxBatchEvents) {
            throw new InvalidArgumentException('Telemetry batch bevat te veel events.');
        }

        foreach ($events as $eventPayload) {
            if (! is_array($eventPayload)) {
                throw new InvalidArgumentException('Elk telemetry event moet een object zijn.');
            }
        }

        /** @var list<array<string, mixed>> $events */
        return $events;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildRecord(array $payload): array
    {
        if (! array_key_exists('type', $payload) || ! array_key_exists('timestamp', $payload) || ! array_key_exists('data', $payload)) {
            throw new InvalidArgumentException('Telemetry payload moet type, timestamp en data bevatten.');
        }

        if (! is_array($payload['data'])) {
            throw new InvalidArgumentException('Telemetry data moet een object zijn.');
        }

        $normalizedType = $this->normalizeEventType((string) $payload['type']);
        if ($normalizedType === '') {
            throw new InvalidArgumentException('Telemetry type ontbreekt of is ongeldig.');
        }

        $sanitizedData = $this->sanitizeValue($payload['data']);
        if (! is_array($sanitizedData)) {
            $sanitizedData = [];
        }

        return [
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
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param array<string, mixed> $record
     */
    private function insertRecord(array $record): int
    {
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

        return (int) $this->telemetryEvents->getInsertID();
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
            ->setTimezone($this->appTimeZone)
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