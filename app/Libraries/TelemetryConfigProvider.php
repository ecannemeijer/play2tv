<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\AppSettingModel;

class TelemetryConfigProvider
{
    private const TELEMETRY_ENABLED_SETTING_KEY = 'telemetry_enabled';
    private const DEFAULT_ENABLED = true;
    private const DEFAULT_SAMPLE_RATE = 0.3;
    private const DEFAULT_BATCH_SIZE = 20;
    private const DEFAULT_FLUSH_INTERVAL_SEC = 30;

    private AppSettingModel $appSettings;

    public function __construct()
    {
        $this->appSettings = new AppSettingModel();
    }

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

    public function setTelemetryEnabled(bool $enabled): void
    {
        $this->appSettings->setValue(self::TELEMETRY_ENABLED_SETTING_KEY, $enabled ? '1' : '0');
    }

    private function resolveTelemetryEnabled(): bool
    {
        $override = $this->appSettings->getValue(self::TELEMETRY_ENABLED_SETTING_KEY);
        if ($override !== null) {
            return $this->parseBool($override);
        }

        return $this->parseBool(env('telemetry.enabled', self::DEFAULT_ENABLED));
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