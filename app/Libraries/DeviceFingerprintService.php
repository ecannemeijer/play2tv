<?php

declare(strict_types=1);

namespace App\Libraries;

class DeviceFingerprintService
{
    private string $salt;

    public function __construct()
    {
        $this->salt = (string) (env('security.fingerprintSalt', env('jwt.secret', 'play2tv-fingerprint')));
    }

    public function buildFingerprintHash(string $userAgent, string $ipAddress, ?string $deviceId = null): string
    {
        return hash('sha256', implode('|', [
            $this->salt,
            $this->normalizeUserAgent($userAgent),
            $this->normalizeDeviceId($deviceId),
        ]));
    }

    public function buildIpHash(string $ipAddress): string
    {
        return hash('sha256', $this->salt . '|' . $this->normalizeIpSegment($ipAddress));
    }

    public function buildUserAgentHash(string $userAgent): string
    {
        return hash('sha256', $this->salt . '|' . $this->normalizeUserAgent($userAgent));
    }

    private function normalizeDeviceId(?string $deviceId): string
    {
        return strtolower(substr(trim((string) $deviceId), 0, 255));
    }

    private function normalizeUserAgent(string $userAgent): string
    {
        $normalized = preg_replace('/\s+/', ' ', strtolower(trim($userAgent))) ?? '';

        return substr($normalized, 0, 255);
    }

    private function normalizeIpSegment(string $ipAddress): string
    {
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $parts = explode('.', $ipAddress);

            return sprintf('%s.%s.%s.0/24', $parts[0], $parts[1], $parts[2]);
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $packed = inet_pton($ipAddress);

            if ($packed !== false) {
                return bin2hex(substr($packed, 0, 8)) . '::/64';
            }
        }

        return 'unknown';
    }
}