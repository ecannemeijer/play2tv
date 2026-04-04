<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Performance extends BaseConfig
{
    /**
     * Cache TTLs for the first API endpoints that benefit most from caching.
     *
     * @var array<string, int>
     */
    public array $apiCacheTtl = [
        'playlist'             => 300,
        'xtream_categories'    => 600,
        'xtream_channels'      => 300,
        'bootstrap_categories' => 600,
    ];

    /**
     * Retention windows in days for high-growth tables.
     *
     * @var array<string, int>
     */
    public array $retentionDays = [
        'refresh_tokens'  => 14,
        'security_events' => 30,
        'ip_logs'         => 30,
        'watch_history'   => 180,
        'sessions'        => 7,
    ];
}
