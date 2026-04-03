<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\SecurityEventModel;
use CodeIgniter\HTTP\IncomingRequest;
use Config\Services;
use Throwable;

class SecurityEventService
{
    private SecurityEventModel $eventModel;
    private DeviceFingerprintService $fingerprints;

    public function __construct()
    {
        $this->eventModel    = new SecurityEventModel();
        $this->fingerprints  = new DeviceFingerprintService();
    }

    /**
     * @param array<string, mixed> $context
     */
    public function log(
        string $eventType,
        string $severity = 'warning',
        ?IncomingRequest $request = null,
        ?int $userId = null,
        array $context = []
    ): void {
        $sanitizedContext = $this->sanitizeContext($context);
        $route            = $request?->getUri()->getPath() ?? (string) ($sanitizedContext['route'] ?? '');
        $ipAddress        = (string) ($sanitizedContext['ip'] ?? $request?->getIPAddress() ?? '');
        $userAgent        = (string) ($sanitizedContext['user_agent'] ?? $request?->getUserAgent()->getAgentString() ?? '');
        $deviceId         = isset($sanitizedContext['device_id']) ? (string) $sanitizedContext['device_id'] : null;

        try {
            $this->eventModel->insert([
                'user_id'          => $userId,
                'event_type'       => substr($eventType, 0, 80),
                'severity'         => substr($severity, 0, 20),
                'ip_hash'          => $ipAddress !== '' ? $this->fingerprints->buildIpHash($ipAddress) : null,
                'fingerprint_hash' => ($ipAddress !== '' || $userAgent !== '')
                    ? $this->fingerprints->buildFingerprintHash($userAgent, $ipAddress, $deviceId)
                    : null,
                'route'            => substr($route, 0, 255),
                'details'          => json_encode($sanitizedContext, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $exception) {
            log_message('warning', 'Failed to persist security event {event}: {message}', [
                'event'   => $eventType,
                'message' => $exception->getMessage(),
            ]);
        }

        log_message($this->toLogLevel($severity), 'Security event {event}: {context}', [
            'event'   => $eventType,
            'context' => json_encode($sanitizedContext, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        $this->dispatchOptionalAlert($eventType, $severity, $sanitizedContext, $userId, $route);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $redactedKeys = [
            'password',
            'token',
            'access_token',
            'refresh_token',
            'authorization',
            'signature',
            'api_key',
            'xtream_password',
        ];

        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $redactedKeys, true)) {
                $context[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $context[$key] = $this->sanitizeContext($value);
                continue;
            }

            if (is_string($value) && strlen($value) > 500) {
                $context[$key] = substr($value, 0, 500);
            }
        }

        return $context;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function dispatchOptionalAlert(string $eventType, string $severity, array $context, ?int $userId, string $route): void
    {
        $webhook = trim((string) env('security.alertWebhook', ''));

        if ($webhook === '' || ! in_array($severity, ['error', 'critical', 'alert'], true)) {
            return;
        }

        try {
            Services::curlrequest()->post($webhook, [
                'timeout' => 0.5,
                'http_errors' => false,
                'json' => [
                    'service'    => 'play2tv-api',
                    'event'      => $eventType,
                    'severity'   => $severity,
                    'user_id'    => $userId,
                    'route'      => $route,
                    'context'    => $context,
                    'occurred_at'=> date(DATE_ATOM),
                ],
            ]);
        } catch (Throwable $exception) {
            log_message('warning', 'Security alert dispatch failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function toLogLevel(string $severity): string
    {
        return match ($severity) {
            'critical', 'alert' => 'critical',
            'error' => 'error',
            default => 'warning',
        };
    }
}