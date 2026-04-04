<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use Throwable;

class RedisService
{
    private static $sharedConnection = null;

    public function getOverview(): array
    {
        $info = $this->info();
        $memoryBytes = (int) ($info['used_memory'] ?? 0);
        $maxmemory = (int) ($info['maxmemory'] ?? 0);

        return [
            'uptime_seconds' => (int) ($info['uptime_in_seconds'] ?? 0),
            'connected_clients' => (int) ($info['connected_clients'] ?? 0),
            'used_memory' => $memoryBytes,
            'used_memory_human' => $this->formatBytes($memoryBytes),
            'maxmemory' => $maxmemory,
            'memory_usage_percent' => $maxmemory > 0 ? round(($memoryBytes / $maxmemory) * 100, 2) : null,
            'evicted_keys' => (int) ($info['evicted_keys'] ?? 0),
            'expired_keys' => (int) ($info['expired_keys'] ?? 0),
            'dbsize' => $this->safeDbSize(),
            'redis_version' => (string) ($info['redis_version'] ?? 'unknown'),
            'mode' => (string) ($info['redis_mode'] ?? 'standalone'),
            'status' => 'LIVE',
            'generated_at' => date(DATE_ATOM),
        ];
    }

    public function getPerformance(): array
    {
        $info = $this->info();
        $hits = (int) ($info['keyspace_hits'] ?? 0);
        $misses = (int) ($info['keyspace_misses'] ?? 0);
        $totalLookups = max(1, $hits + $misses);

        return [
            'commands_per_sec' => (float) ($info['instantaneous_ops_per_sec'] ?? 0),
            'hits' => $hits,
            'misses' => $misses,
            'hit_rate' => round(($hits / $totalLookups) * 100, 2),
            'input_kbps' => round((float) ($info['instantaneous_input_kbps'] ?? 0), 2),
            'output_kbps' => round((float) ($info['instantaneous_output_kbps'] ?? 0), 2),
            'rejected_connections' => (int) ($info['rejected_connections'] ?? 0),
            'total_connections_received' => (int) ($info['total_connections_received'] ?? 0),
            'total_commands_processed' => (int) ($info['total_commands_processed'] ?? 0),
            'latest_fork_usec' => (int) ($info['latest_fork_usec'] ?? 0),
        ];
    }

    public function getMemory(): array
    {
        $info = $this->info();
        $ttlStats = $this->sampleTtlStats();
        $usedMemory = (int) ($info['used_memory'] ?? 0);
        $peakMemory = (int) ($info['used_memory_peak'] ?? 0);

        return [
            'used_memory' => $usedMemory,
            'used_memory_human' => $this->formatBytes($usedMemory),
            'used_memory_peak' => $peakMemory,
            'used_memory_peak_human' => $this->formatBytes($peakMemory),
            'used_memory_rss' => (int) ($info['used_memory_rss'] ?? 0),
            'used_memory_rss_human' => $this->formatBytes((int) ($info['used_memory_rss'] ?? 0)),
            'mem_fragmentation_ratio' => round((float) ($info['mem_fragmentation_ratio'] ?? 0), 2),
            'maxmemory_policy' => (string) ($info['maxmemory_policy'] ?? 'noeviction'),
            'ttl' => $ttlStats,
            'allocator' => (string) ($info['mem_allocator'] ?? 'unknown'),
        ];
    }

    public function getKeyStats(): array
    {
        $sample = $this->scanKeys('*', min($this->getScanSampleLimit(), 100));
        $prefixBuckets = [];

        foreach ($sample['keys'] as $item) {
            $prefix = $this->derivePrefix($item['key']);
            $prefixBuckets[$prefix] = ($prefixBuckets[$prefix] ?? 0) + 1;
        }

        arsort($prefixBuckets);

        return [
            'dbsize' => $this->safeDbSize(),
            'sample_size' => count($sample['keys']),
            'sampled_prefixes' => array_slice($prefixBuckets, 0, 10, true),
            'recent_keys' => array_slice($sample['keys'], 0, 20),
        ];
    }

    public function getSlowLog(): array
    {
        $redis = $this->connection();
        $limit = max(1, (int) env('redis.slowlogLimit', 25));
        $entries = $redis->rawCommand('SLOWLOG', 'GET', $limit);

        if (! is_array($entries)) {
            return [];
        }

        return array_map(static function ($entry): array {
            return [
                'id' => (int) ($entry[0] ?? 0),
                'timestamp' => isset($entry[1]) ? date(DATE_ATOM, (int) $entry[1]) : null,
                'duration_microseconds' => (int) ($entry[2] ?? 0),
                'command' => implode(' ', array_map(static fn($value): string => (string) $value, $entry[3] ?? [])),
                'client' => (string) ($entry[4] ?? ''),
                'name' => (string) ($entry[5] ?? ''),
            ];
        }, $entries);
    }

    public function getIptvStats(): array
    {
        return [
            'active_users' => $this->countByPrefixes($this->envList('redis.iptv.userPrefixes', ['play2tv:session:', 'play2tv:user:session:', 'iptv:user:'])),
            'active_streams' => $this->countByPrefixes($this->envList('redis.iptv.streamPrefixes', ['play2tv:stream:active:', 'iptv:stream:', 'stream:active:'])),
            'cache_hits' => [
                'epg' => $this->firstIntegerValue($this->envList('redis.iptv.epgHitKeys', ['play2tv:metrics:epg:hits', 'metrics:epg:hits', 'cache:epg:hits'])),
                'vod' => $this->firstIntegerValue($this->envList('redis.iptv.vodHitKeys', ['play2tv:metrics:vod:hits', 'metrics:vod:hits', 'cache:vod:hits'])),
            ],
        ];
    }

    public function scanKeys(string $pattern, ?int $limit = null): array
    {
        $redis = $this->connection();
        $sanitizedPattern = $this->normalizePattern($pattern);
        $targetLimit = $limit ?? max(1, (int) env('redis.keySearchLimit', 100));
        $scanCount = max(50, (int) env('redis.scanCount', 200));
        $iterator = null;
        $items = [];
        $visited = 0;

        do {
            $batch = $redis->scan($iterator, $sanitizedPattern, $scanCount);

            if ($batch === false) {
                continue;
            }

            foreach ($batch as $key) {
                $items[] = $this->describeKey((string) $key);
                $visited++;

                if (count($items) >= $targetLimit || $visited >= $this->getScanSampleLimit()) {
                    break 2;
                }
            }
        } while ($iterator !== 0);

        return [
            'pattern' => $sanitizedPattern,
            'count' => count($items),
            'keys' => $items,
        ];
    }

    public function deleteKey(string $key): bool
    {
        if (! filter_var(env('redis.keyDeleteEnabled', true), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('Key deletion is disabled.');
        }

        return $this->connection()->del($key) > 0;
    }

    public function flushPrefix(string $prefix): array
    {
        $allowedPrefixes = $this->envList('redis.admin.flushablePrefixes', ['play2tv:', 'cache:', 'epg:', 'vod:']);

        if (! in_array($prefix, $allowedPrefixes, true)) {
            throw new RuntimeException('Prefix is not allowed for flush.');
        }

        $deleted = 0;
        $redis = $this->connection();
        $iterator = null;
        $pattern = $prefix . '*';
        $scanCount = max(50, (int) env('redis.scanCount', 200));

        do {
            $batch = $redis->scan($iterator, $pattern, $scanCount);

            if ($batch === false || $batch === []) {
                continue;
            }

            $deleted += (int) $redis->del($batch);
        } while ($iterator !== 0);

        return [
            'prefix' => $prefix,
            'deleted' => $deleted,
        ];
    }

    public function execute(string $command): array
    {
        $trimmed = trim($command);
        if ($trimmed === '') {
            throw new RuntimeException('Redis command is required.');
        }

        $tokens = preg_split('/\s+/', $trimmed) ?: [];
        $normalized = strtolower(implode(' ', array_slice($tokens, 0, min(2, count($tokens)))));
        $baseCommand = strtolower((string) ($tokens[0] ?? ''));
        $allowed = array_map([$this, 'normalizeAllowedCommand'], $this->envList('redis.admin.allowedCommands', ['ping', 'info', 'ttl', 'pttl', 'type', 'exists', 'get', 'hgetall', 'hget', 'llen', 'scard', 'zcard', 'memory_usage']));
        $blocked = ['flushall', 'flushdb', 'eval', 'evalsha', 'script', 'config', 'keys', 'shutdown', 'debug', 'migrate', 'restore', 'replicaof', 'slaveof', 'monitor'];

        if (in_array($baseCommand, $blocked, true)) {
            throw new RuntimeException('This Redis command is blocked.');
        }

        if (! in_array($normalized, $allowed, true) && ! in_array($baseCommand, $allowed, true)) {
            throw new RuntimeException('This Redis command is not in the safe allowlist.');
        }

        $result = $this->connection()->rawCommand(...$tokens);

        return [
            'command' => $trimmed,
            'result' => $result,
        ];
    }

    public function buildDashboardSnapshot(): array
    {
        $overview = $this->getOverview();
        $performance = $this->getPerformance();
        $memory = $this->getMemory();

        return [
            'overview' => $overview,
            'performance' => $performance,
            'memory' => $memory,
            'keys' => $this->getKeyStats(),
            'slowlog' => $this->getSlowLog(),
            'iptv' => $this->getIptvStats(),
            'alerts' => $this->buildAlerts($overview, $performance, $memory),
        ];
    }

    /**
     * @return list<string>
     */
    public function getFlushablePrefixes(): array
    {
        return $this->envList('redis.admin.flushablePrefixes', ['play2tv:', 'cache:', 'epg:', 'vod:']);
    }

    /**
     * @return list<string>
     */
    public function getAllowedCommands(): array
    {
        return array_map([$this, 'normalizeAllowedCommand'], $this->envList('redis.admin.allowedCommands', ['ping', 'info', 'ttl', 'pttl', 'type', 'exists', 'get', 'hgetall', 'hget', 'llen', 'scard', 'zcard', 'memory_usage']));
    }

    public function connection()
    {
        if (is_object(self::$sharedConnection)) {
            try {
                $pong = self::$sharedConnection->ping();
                if ($pong === true || $pong === '+PONG' || $pong === 'PONG' || $pong === 1) {
                    return self::$sharedConnection;
                }
            } catch (Throwable) {
                self::$sharedConnection = null;
            }
        }

        if (! class_exists('Redis')) {
            throw new RuntimeException('PHP Redis extension is required for the Redis dashboard.');
        }

        $redisClass = 'Redis';
        $redis = new $redisClass();
        $host = (string) env('redis.host', '127.0.0.1');
        $port = (int) env('redis.port', 6379);
        $connectTimeout = (float) env('redis.connectTimeout', 1.5);
        $readTimeout = (float) env('redis.readTimeout', 1.5);

        $redis->connect($host, $port, $connectTimeout, null, 0, $readTimeout);

        $password = env('redis.password');
        if (is_string($password) && $password !== '') {
            $redis->auth($password);
        }

        $database = (int) env('redis.database', 0);
        if ($database > 0) {
            $redis->select($database);
        }

        self::$sharedConnection = $redis;

        return self::$sharedConnection;
    }

    private function info(): array
    {
        $info = $this->connection()->info();

        return is_array($info) ? $info : [];
    }

    private function describeKey(string $key): array
    {
        $redis = $this->connection();
        $ttl = $redis->ttl($key);
        $typeCode = $redis->type($key);

        return [
            'key' => $key,
            'ttl' => $ttl,
            'ttl_human' => $this->formatTtl($ttl),
            'type' => $this->mapType($typeCode),
            'memory_usage' => $this->memoryUsage($key),
        ];
    }

    private function sampleTtlStats(): array
    {
        $redis = $this->connection();
        $iterator = null;
        $withTtl = 0;
        $withoutTtl = 0;
        $expired = 0;
        $sampled = 0;
        $scanCount = max(50, (int) env('redis.scanCount', 200));
        $sampleLimit = $this->getScanSampleLimit();

        do {
            $batch = $redis->scan($iterator, '*', $scanCount);

            if ($batch === false) {
                continue;
            }

            foreach ($batch as $key) {
                $ttl = (int) $redis->ttl((string) $key);
                $sampled++;

                if ($ttl === -1) {
                    $withoutTtl++;
                } elseif ($ttl === -2) {
                    $expired++;
                } else {
                    $withTtl++;
                }

                if ($sampled >= $sampleLimit) {
                    break 2;
                }
            }
        } while ($iterator !== 0);

        return [
            'sampled' => $sampled,
            'with_ttl' => $withTtl,
            'without_ttl' => $withoutTtl,
            'expired' => $expired,
            'without_ttl_warning' => $withoutTtl > 0,
        ];
    }

    private function countByPrefixes(array $prefixes): int
    {
        $total = 0;

        foreach ($prefixes as $prefix) {
            $total += $this->countScanMatches($prefix . '*');
        }

        return $total;
    }

    private function countScanMatches(string $pattern): int
    {
        $redis = $this->connection();
        $iterator = null;
        $count = 0;
        $scanCount = max(50, (int) env('redis.scanCount', 200));

        do {
            $batch = $redis->scan($iterator, $pattern, $scanCount);

            if ($batch === false) {
                continue;
            }

            $count += count($batch);

            if ($count >= $this->getScanSampleLimit()) {
                break;
            }
        } while ($iterator !== 0);

        return $count;
    }

    private function firstIntegerValue(array $keys): int
    {
        $redis = $this->connection();

        foreach ($keys as $key) {
            $value = $redis->get($key);

            if ($value !== false && is_numeric($value)) {
                return (int) $value;
            }
        }

        return 0;
    }

    private function safeDbSize(): int
    {
        try {
            return (int) $this->connection()->dbSize();
        } catch (Throwable) {
            return 0;
        }
    }

    private function memoryUsage(string $key): int
    {
        try {
            return (int) $this->connection()->rawCommand('MEMORY', 'USAGE', $key);
        } catch (Throwable) {
            return 0;
        }
    }

    private function buildAlerts(array $overview, array $performance, array $memory): array
    {
        $alerts = [];

        if (($overview['memory_usage_percent'] ?? 0) !== null && (float) $overview['memory_usage_percent'] > 80) {
            $alerts[] = [
                'level' => 'warning',
                'message' => 'Memory usage exceeds 80% of configured maxmemory.',
            ];
        }

        if ((int) ($overview['evicted_keys'] ?? 0) > 0) {
            $alerts[] = [
                'level' => 'danger',
                'message' => 'Redis has evicted keys. Review maxmemory and eviction policy.',
            ];
        }

        if ((int) ($performance['latest_fork_usec'] ?? 0) > 500000) {
            $alerts[] = [
                'level' => 'warning',
                'message' => 'High Redis latency detected from fork duration.',
            ];
        }

        if ((bool) ($memory['ttl']['without_ttl_warning'] ?? false)) {
            $alerts[] = [
                'level' => 'warning',
                'message' => 'Sampled keys without TTL detected. Review cache hygiene.',
            ];
        }

        return $alerts;
    }

    private function mapType(int $typeCode): string
    {
        return match ($typeCode) {
            1 => 'string',
            2 => 'set',
            3 => 'list',
            4 => 'zset',
            5 => 'hash',
            6 => 'stream',
            default => 'unknown',
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return number_format($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }

    private function formatTtl(int $ttl): string
    {
        if ($ttl === -1) {
            return 'No expiry';
        }

        if ($ttl === -2) {
            return 'Missing';
        }

        if ($ttl < 60) {
            return $ttl . 's';
        }

        $hours = intdiv($ttl, 3600);
        $minutes = intdiv($ttl % 3600, 60);

        return sprintf('%dh %dm', $hours, $minutes);
    }

    private function derivePrefix(string $key): string
    {
        $parts = explode(':', $key);

        if (count($parts) <= 1) {
            return $key;
        }

        return implode(':', array_slice($parts, 0, min(2, count($parts)))) . ':';
    }

    private function normalizePattern(string $pattern): string
    {
        $trimmed = trim($pattern);

        if ($trimmed === '' || $trimmed === '*') {
            return '*';
        }

        if (! str_contains($trimmed, '*')) {
            return $trimmed . '*';
        }

        return $trimmed;
    }

    private function normalizeAllowedCommand(string $command): string
    {
        return str_replace('_', ' ', strtolower(trim($command)));
    }

    /**
     * @param list<string> $default
     * @return list<string>
     */
    private function envList(string $key, array $default): array
    {
        $raw = trim((string) env($key, implode(',', $default)));
        $values = array_values(array_filter(array_map('trim', explode(',', $raw)), static fn(string $value): bool => $value !== ''));

        return $values === [] ? $default : $values;
    }

    private function getScanSampleLimit(): int
    {
        return max(100, (int) env('redis.scanSampleLimit', 500));
    }
}