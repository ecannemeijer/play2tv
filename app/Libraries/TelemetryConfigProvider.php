<?php

declare(strict_types=1);

namespace App\Libraries;

use RuntimeException;
use Throwable;

class TelemetryConfigProvider
{
    private const STORAGE_FILE_NAME = 'telemetry-config.json';
    private const TELEMETRY_ENABLED_SETTING_KEY = 'telemetry_enabled';
    private const DEFAULT_ENABLED = true;
    private const DEFAULT_SAMPLE_RATE = 0.3;
    private const DEFAULT_BATCH_SIZE = 20;
    private const DEFAULT_FLUSH_INTERVAL_SEC = 30;

    /**
     * @return array<string, bool|float|int>
     */
    public function getConfig(): array
    {
        return [
            'telemetry_enabled' => $this->resolveTelemetryEnabled(),
            'telemetry_sample_rate' => $this->clampFloat(env('telemetry.sampleRate', self::DEFAULT_SAMPLE_RATE), 0.0, 1.0, self::DEFAULT_SAMPLE_RATE),
            'telemetry_batch_size' => $this->clampInt(env('telemetry.batchSize', self::DEFAULT_BATCH_SIZE), 1, 100, self::DEFAULT_BATCH_SIZE),
            'telemetry_flush_interval_sec' => $this->clampInt(env('telemetry.flushIntervalSec', self::DEFAULT_FLUSH_INTERVAL_SEC), 5, 300, self::DEFAULT_FLUSH_INTERVAL_SEC),
        ];
    }

    public function setTelemetryEnabled(bool $enabled): bool
    {
        try {
            $storedConfig = $this->readStoredConfig();
            $storedConfig[self::TELEMETRY_ENABLED_SETTING_KEY] = $enabled;
            $this->writeStoredConfig($storedConfig);

            return true;
        } catch (Throwable $exception) {
            log_message('warning', 'Unable to persist telemetry killswitch override: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function resolveTelemetryEnabled(): bool
    {
        try {
            $storedConfig = $this->readStoredConfig();
            if (array_key_exists(self::TELEMETRY_ENABLED_SETTING_KEY, $storedConfig)) {
                return $this->parseBool($storedConfig[self::TELEMETRY_ENABLED_SETTING_KEY]);
            }
        } catch (Throwable $exception) {
            log_message('warning', 'Unable to read telemetry killswitch override: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }

        return $this->parseBool(env('telemetry.enabled', self::DEFAULT_ENABLED));
    }

    /**
     * @return array<string, mixed>
     */
    private function readStoredConfig(): array
    {
        $storagePath = $this->storagePath();
        if (! is_file($storagePath)) {
            return [];
        }

        $rawJson = @file_get_contents($storagePath);
        if ($rawJson === false || trim($rawJson) === '') {
            return [];
        }

        $decoded = json_decode($rawJson, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function writeStoredConfig(array $config): void
    {
        $storagePath = $this->storagePath();
        $directory = dirname($storagePath);
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Telemetry config map kon niet worden aangemaakt.');
        }

        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Telemetry config kon niet worden gecodeerd.');
        }

        if (@file_put_contents($storagePath, $json, LOCK_EX) === false) {
            throw new RuntimeException('Telemetry config kon niet worden opgeslagen.');
        }
    }

    private function storagePath(): string
    {
        return rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::STORAGE_FILE_NAME;
    }

    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => self::DEFAULT_ENABLED,
        };
    }

    private function clampFloat(mixed $value, float $min, float $max, float $fallback): float
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        return min($max, max($min, (float) $value));
    }

    private function clampInt(mixed $value, int $min, int $max, int $fallback): int
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        return min($max, max($min, (int) $value));
    }
}