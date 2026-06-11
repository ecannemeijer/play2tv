<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\Performance;
use Config\Services;

class ApiCacheService
{
    private \CodeIgniter\Cache\CacheInterface $cache;
    private Performance $config;

    public function __construct()
    {
        $this->cache  = Services::cache();
        $this->config = config(Performance::class);
    }

    /**
     * @template T
     * @param callable():T $resolver
     * @return T
     */
    public function remember(string $key, int $ttl, callable $resolver): mixed
    {
        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $resolver();
        $this->cache->save($key, $value, $ttl);

        return $value;
    }

    /**
     * @param array<string, mixed> $user
     * @param callable():array<int, array<string, mixed>> $resolver
     * @return array<int, array<string, mixed>>
     */
    public function rememberXtreamCategories(array $user, string $type, callable $resolver): array
    {
        $key = implode('-', [
            'perf',
            'xtream',
            'categories',
            (string) $user['id'],
            $this->credentialsHash($user),
            $type,
        ]);

        return $this->remember($key, $this->ttl('xtream_categories'), $resolver);
    }

    /**
     * @param array<string, mixed> $user
     * @param callable():array<int, array<string, mixed>> $resolver
     * @return array<int, array<string, mixed>>
     */
    public function rememberXtreamChannels(array $user, string $type, string $categoryId, callable $resolver): array
    {
        $key = implode('-', [
            'perf',
            'xtream',
            'channels',
            (string) $user['id'],
            $this->credentialsHash($user),
            $type,
            $categoryId !== '' ? $categoryId : 'all',
        ]);

        return $this->remember($key, $this->ttl('xtream_channels'), $resolver);
    }

    /**
     * @param array<string, mixed> $user
     * @param callable():array<int, array<string, mixed>> $resolver
     * @return array<int, array<string, mixed>>
     */
    public function rememberBootstrapCategories(array $user, string $type, callable $resolver): array
    {
        $key = implode('-', [
            'perf',
            'bootstrap',
            'categories',
            (string) $user['id'],
            $this->credentialsHash($user),
            $type,
        ]);

        return $this->remember($key, $this->ttl('bootstrap_categories'), $resolver);
    }

    /**
     * @param array<string, mixed> $user
     */
    private function credentialsHash(array $user): string
    {
        return sha1(implode('|', [
            (string) ($user['xtream_server'] ?? ''),
            (string) ($user['xtream_username'] ?? ''),
            (string) ($user['xtream_password'] ?? ''),
        ]));
    }

    private function ttl(string $key): int
    {
        return max(30, (int) ($this->config->apiCacheTtl[$key] ?? 60));
    }
}