<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\Performance;
use Config\Services;

class ApiCacheService
{
    private const PLAYLIST_VERSION_KEY = 'perf-playlist-version';

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
     * @param callable():array<string, mixed>|null $resolver
     * @return array<string, mixed>|null
     */
    public function rememberActivePlaylist(callable $resolver): ?array
    {
        $key = implode('-', [
            'perf',
            'playlist',
            $this->getPlaylistVersion(),
        ]);

        return $this->remember($key, $this->ttl('playlist'), $resolver);
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

    public function bumpPlaylistVersion(): void
    {
        $this->cache->save(self::PLAYLIST_VERSION_KEY, $this->newVersionToken(), DAY);
    }

    private function getPlaylistVersion(): string
    {
        $version = $this->cache->get(self::PLAYLIST_VERSION_KEY);
        if (! is_string($version) || $version === '') {
            $version = $this->newVersionToken();
            $this->cache->save(self::PLAYLIST_VERSION_KEY, $version, DAY);
        }

        return $version;
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

    private function newVersionToken(): string
    {
        return bin2hex(random_bytes(8));
    }
}