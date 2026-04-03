<?php

namespace Config;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Cache\Handlers\ApcuHandler;
use CodeIgniter\Cache\Handlers\DummyHandler;
use CodeIgniter\Cache\Handlers\FileHandler;
use CodeIgniter\Cache\Handlers\MemcachedHandler;
use CodeIgniter\Cache\Handlers\PredisHandler;
use CodeIgniter\Cache\Handlers\RedisHandler;
use CodeIgniter\Cache\Handlers\WincacheHandler;
use CodeIgniter\Config\BaseConfig;

class Cache extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Primary Handler
     * --------------------------------------------------------------------------
     *
     * The name of the preferred handler that should be used. If for some reason
     * it is not available, the $backupHandler will be used in its place.
     */
    public string $handler = 'file';

    /**
     * --------------------------------------------------------------------------
     * Backup Handler
     * --------------------------------------------------------------------------
     *
     * The name of the handler that will be used in case the first one is
     * unreachable. Often, 'file' is used here since the filesystem is
     * always available, though that's not always practical for the app.
     */
    public string $backupHandler = 'dummy';

    /**
     * --------------------------------------------------------------------------
     * Key Prefix
     * --------------------------------------------------------------------------
     *
     * This string is added to all cache item names to help avoid collisions
     * if you run multiple applications with the same cache engine.
     */
    public string $prefix = '';

    /**
     * --------------------------------------------------------------------------
     * Default TTL
     * --------------------------------------------------------------------------
     *
     * The default number of seconds to save items when none is specified.
     *
     * WARNING: This is not used by framework handlers where 60 seconds is
     * hard-coded, but may be useful to projects and modules. This will replace
     * the hard-coded value in a future release.
     */
    public int $ttl = 60;

    /**
     * --------------------------------------------------------------------------
     * Reserved Characters
     * --------------------------------------------------------------------------
     *
     * A string of reserved characters that will not be allowed in keys or tags.
     * Strings that violate this restriction will cause handlers to throw.
     * Default: {}()/\@:
     *
     * NOTE: The default set is required for PSR-6 compliance.
     */
    public string $reservedCharacters = '{}()/\@:';

    /**
     * --------------------------------------------------------------------------
     * File settings
     * --------------------------------------------------------------------------
     *
     * Your file storage preferences can be specified below, if you are using
     * the File driver.
     *
     * @var array{storePath?: string, mode?: int}
     */
    public array $file = [
        'storePath' => WRITEPATH . 'cache/',
        'mode'      => 0640,
    ];

    /**
     * -------------------------------------------------------------------------
     * Memcached settings
     * -------------------------------------------------------------------------
     *
     * Your Memcached servers can be specified below, if you are using
     * the Memcached drivers.
     *
     * @see https://codeigniter.com/user_guide/libraries/caching.html#memcached
     *
     * @var array{host?: string, port?: int, weight?: int, raw?: bool}
     */
    public array $memcached = [
        'host'   => '127.0.0.1',
        'port'   => 11211,
        'weight' => 1,
        'raw'    => false,
    ];

    /**
     * -------------------------------------------------------------------------
     * Redis settings
     * -------------------------------------------------------------------------
     *
     * Your Redis server can be specified below, if you are using
     * the Redis or Predis drivers.
     *
     * @var array{
     *     host?: string,
     *     password?: string|null,
     *     port?: int,
     *     timeout?: int,
     *     async?: bool,
     *     persistent?: bool,
     *     database?: int
     * }
     */
    public array $redis = [
        'host'       => '127.0.0.1',
        'password'   => null,
        'port'       => 6379,
        'timeout'    => 0,
        'async'      => false, // specific to Predis and ignored by the native Redis extension
        'persistent' => false,
        'database'   => 0,
    ];

    /**
     * --------------------------------------------------------------------------
     * Available Cache Handlers
     * --------------------------------------------------------------------------
     *
     * This is an array of cache engine alias' and class names. Only engines
     * that are listed here are allowed to be used.
     *
     * @var array<string, class-string<CacheInterface>>
     */
    public array $validHandlers = [
        'apcu'      => ApcuHandler::class,
        'dummy'     => DummyHandler::class,
        'file'      => FileHandler::class,
        'memcached' => MemcachedHandler::class,
        'predis'    => PredisHandler::class,
        'redis'     => RedisHandler::class,
        'wincache'  => WincacheHandler::class,
    ];

    /**
     * --------------------------------------------------------------------------
     * Web Page Caching: Cache Include Query String
     * --------------------------------------------------------------------------
     *
     * Whether to take the URL query string into consideration when generating
     * output cache files. Valid options are:
     *
     *    false = Disabled
     *    true  = Enabled, take all query parameters into account.
     *            Please be aware that this may result in numerous cache
     *            files generated for the same page over and over again.
     *    ['q'] = Enabled, but only take into account the specified list
     *            of query parameters.
     *
     * @var bool|list<string>
     */
    public $cacheQueryString = false;

    /**
     * --------------------------------------------------------------------------
     * Web Page Caching: Cache Status Codes
     * --------------------------------------------------------------------------
     *
     * HTTP status codes that are allowed to be cached. Only responses with
     * these status codes will be cached by the PageCache filter.
     *
     * Default: [] - Cache all status codes (backward compatible)
     *
     * Recommended: [200] - Only cache successful responses
     *
     * You can also use status codes like:
     *   [200, 404, 410] - Cache successful responses and specific error codes
     *   [200, 201, 202, 203, 204] - All 2xx successful responses
     *
     * WARNING: Using [] may cache temporary error pages (404, 500, etc).
     * Consider restricting to [200] for production applications to avoid
     * caching errors that should be temporary.
     *
     * @var list<int>
     */
    public array $cacheStatusCodes = [];

    public function __construct()
    {
        parent::__construct();

        $handler = strtolower(trim((string) env('cache.handler', $this->handler)));
        if ($handler !== '' && isset($this->validHandlers[$handler])) {
            $this->handler = $handler;
        }

        $backupHandler = strtolower(trim((string) env('cache.backupHandler', $this->backupHandler)));
        if ($backupHandler !== '' && isset($this->validHandlers[$backupHandler])) {
            $this->backupHandler = $backupHandler;
        }

        $this->prefix = (string) env('cache.prefix', $this->prefix !== '' ? $this->prefix : 'play2tv:');
        $this->ttl    = max(30, (int) env('cache.ttl', $this->ttl));

        $this->redis['host']       = (string) env('cache.redis.host', (string) $this->redis['host']);
        $this->redis['password']   = env('cache.redis.password', $this->redis['password']);
        $this->redis['port']       = (int) env('cache.redis.port', (int) $this->redis['port']);
        $this->redis['timeout']    = (int) env('cache.redis.timeout', (int) $this->redis['timeout']);
        $this->redis['persistent'] = (bool) env('cache.redis.persistent', (bool) $this->redis['persistent']);
        $this->redis['database']   = (int) env('cache.redis.database', (int) $this->redis['database']);
    }
}
