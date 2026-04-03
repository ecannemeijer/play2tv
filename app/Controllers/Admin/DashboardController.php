<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Config\Cache;
use Config\Performance;
use Config\Session;
use App\Models\UserModel;
use App\Models\WatchHistoryModel;
use App\Models\StorePointsModel;
use App\Models\UserIpsLogModel;
use CodeIgniter\Controller;

/**
 * DashboardController
 *
 * Admin dashboard showing platform statistics.
 * All statistics used by Chart.js charts on the dashboard.
 *
 * Route: GET /admin/dashboard (AdminAuthFilter required)
 */
class DashboardController extends Controller
{
    private UserModel         $userModel;
    private WatchHistoryModel $historyModel;
    private StorePointsModel  $pointsModel;
    private UserIpsLogModel   $ipsModel;

    public function __construct()
    {
        $this->userModel   = new UserModel();
        $this->historyModel = new WatchHistoryModel();
        $this->pointsModel  = new StorePointsModel();
        $this->ipsModel     = new UserIpsLogModel();
        helper(['url', 'form']);
    }

    public function index()
    {
        $db = \Config\Database::connect();

        // ── Total users ───────────────────────────────────────────────────────
        $totalUsers = $this->userModel->countAllResults();

        // ── Active users last 24h (unique user IDs in ip log) ────────────────
        $activeUsers24h = (int) $db->table('user_ips_log')
            ->distinct()
            ->select('user_id')
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->countAllResults();

        // ── Premium users ─────────────────────────────────────────────────────
        $premiumUsers = $this->userModel
            ->where('premium', 1)
            ->where('(premium_until IS NULL OR premium_until > NOW())')
            ->countAllResults();

        // ── Total watch events ────────────────────────────────────────────────
        $totalWatchEvents = $this->historyModel->getTotalWatchEvents();

        // ── Most watched content (top 10) ─────────────────────────────────────
        $mostWatched = $this->historyModel->getMostWatched(10);

        // ── Total store points distributed ────────────────────────────────────
        $totalPoints = $this->pointsModel->getTotalPointsDistributed();

        // ── New registrations per day last 7 days (for Chart.js) ─────────────
        $registrations = $db->table('users')
            ->select("DATE(created_at) AS day, COUNT(*) AS count")
            ->where('created_at >=', date('Y-m-d', strtotime('-7 days')))
            ->groupBy("DATE(created_at)")
            ->orderBy('day', 'ASC')
            ->get()
            ->getResultArray();

        // Fill missing days with 0
        $chartDays   = [];
        $chartCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $day     = date('Y-m-d', strtotime("-{$i} days"));
            $dayLabel = date('d M', strtotime($day));
            $chartDays[] = $dayLabel;
            $found = array_filter($registrations, fn($r) => $r['day'] === $day);
            $chartCounts[] = $found ? (int) reset($found)['count'] : 0;
        }

        // ── Watch events per day last 7 days (for Chart.js) ──────────────────
        $watchPerDay = $db->table('watch_history')
            ->select("DATE(watched_at) AS day, COUNT(*) AS count")
            ->where('watched_at >=', date('Y-m-d', strtotime('-7 days')))
            ->groupBy("DATE(watched_at)")
            ->orderBy('day', 'ASC')
            ->get()
            ->getResultArray();

        $watchCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $day     = date('Y-m-d', strtotime("-{$i} days"));
            $found = array_filter($watchPerDay, fn($r) => $r['day'] === $day);
            $watchCounts[] = $found ? (int) reset($found)['count'] : 0;
        }

        return view('admin/dashboard', [
            'title'            => 'Dashboard — Play2TV Admin',
            'totalUsers'       => $totalUsers,
            'activeUsers24h'   => $activeUsers24h,
            'premiumUsers'     => $premiumUsers,
            'totalWatchEvents' => $totalWatchEvents,
            'mostWatched'      => $mostWatched,
            'totalPoints'      => $totalPoints,
            'health'           => $this->buildHealthBlock(),
            'chartDays'        => json_encode($chartDays),
            'chartRegCounts'   => json_encode($chartCounts),
            'chartWatchCounts' => json_encode($watchCounts),
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildHealthBlock(): array
    {
        return [
            'database' => $this->databaseHealth(),
            'redis' => $this->redisHealth(),
            'cache' => $this->cacheHealth(),
            'prune' => $this->pruneHealth(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseHealth(): array
    {
        try {
            $db = \Config\Database::connect();
            $db->query('SELECT 1');

            return [
                'status' => 'ok',
                'label' => 'DB',
                'message' => 'Databaseverbinding werkt.',
                'meta' => [
                    'driver' => $db->DBDriver,
                    'database' => $db->database,
                ],
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'label' => 'DB',
                'message' => $exception->getMessage(),
                'meta' => [],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function cacheHealth(): array
    {
        $cacheConfig = config(Cache::class);
        $cache = service('cache');
        $key = 'admin_health_' . bin2hex(random_bytes(4));

        try {
            $cache->save($key, 'ok', 30);
            $read = $cache->get($key);
            $cache->delete($key);

            if ($read !== 'ok') {
                throw new \RuntimeException('Cache read/write check gaf geen geldige waarde terug.');
            }

            return [
                'status' => 'ok',
                'label' => 'Cache',
                'message' => 'Cache read/write check geslaagd.',
                'meta' => [
                    'handler' => $cacheConfig->handler,
                    'backup' => $cacheConfig->backupHandler,
                ],
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'label' => 'Cache',
                'message' => $exception->getMessage(),
                'meta' => [
                    'handler' => $cacheConfig->handler,
                    'backup' => $cacheConfig->backupHandler,
                ],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function redisHealth(): array
    {
        $cacheConfig = config(Cache::class);
        $sessionConfig = config(Session::class);
        $redisRequired = $cacheConfig->handler === 'redis' || str_contains($sessionConfig->driver, 'RedisHandler');

        if (! $redisRequired) {
            return [
                'status' => 'warning',
                'label' => 'Redis',
                'message' => 'Redis is niet actief geconfigureerd voor cache of sessies.',
                'meta' => [],
            ];
        }

        if (! class_exists('Redis')) {
            return [
                'status' => 'error',
                'label' => 'Redis',
                'message' => 'PHP Redis extensie ontbreekt terwijl Redis is geconfigureerd.',
                'meta' => [],
            ];
        }

        $host = (string) ($cacheConfig->redis['host'] ?? '127.0.0.1');
        $port = (int) ($cacheConfig->redis['port'] ?? 6379);
        $password = $cacheConfig->redis['password'];
        $database = (int) ($cacheConfig->redis['database'] ?? 0);

        if ($cacheConfig->handler !== 'redis' && str_contains($sessionConfig->driver, 'RedisHandler')) {
            $parsed = $this->parseRedisSavePath($sessionConfig->savePath);
            $host = $parsed['host'];
            $port = $parsed['port'];
            $password = $parsed['password'];
            $database = $parsed['database'];
        }

        try {
            $redisClass = 'Redis';
            $redis = new $redisClass();
            $redis->connect($host, $port, 1.5);
            if ($password !== null && $password !== '') {
                $redis->auth((string) $password);
            }
            $redis->select($database);
            $pong = $redis->ping();
            $redis->close();

            $isHealthy = $pong === true || $pong === 1 || str_contains(strtolower((string) $pong), 'pong');

            return [
                'status' => $isHealthy ? 'ok' : 'warning',
                'label' => 'Redis',
                'message' => 'Redis ping ' . ($pong === true ? 'PONG' : (string) $pong),
                'meta' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                ],
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'error',
                'label' => 'Redis',
                'message' => $exception->getMessage(),
                'meta' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                ],
            ];
        }
    }

    /**
     * @return array{host:string, port:int, password:string|null, database:int}
     */
    private function parseRedisSavePath(string $savePath): array
    {
        $parts = parse_url($savePath);
        parse_str((string) ($parts['query'] ?? ''), $query);

        return [
            'host' => (string) ($parts['host'] ?? '127.0.0.1'),
            'port' => (int) ($parts['port'] ?? 6379),
            'password' => isset($query['auth']) ? urldecode((string) $query['auth']) : null,
            'database' => isset($query['database']) ? (int) $query['database'] : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pruneHealth(): array
    {
        $db = \Config\Database::connect();
        $performance = config(Performance::class);
        $pending = [];
        $total = 0;

        foreach ([
            'auth_refresh_tokens' => ['expires_at', max(1, (int) $performance->retentionDays['refresh_tokens'])],
            'security_events' => ['created_at', max(1, (int) $performance->retentionDays['security_events'])],
            'user_ips_log' => ['created_at', max(1, (int) $performance->retentionDays['ip_logs'])],
            'watch_history' => ['watched_at', max(1, (int) $performance->retentionDays['watch_history'])],
            'ci_sessions' => ['timestamp', max(1, (int) $performance->retentionDays['sessions'])],
        ] as $table => [$column, $days]) {
            if (! $db->tableExists($table)) {
                continue;
            }

            $cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
            $count = $db->table($table)->where($column . ' <', $cutoff)->countAllResults();
            $pending[$table] = $count;
            $total += $count;
        }

        return [
            'status' => $total === 0 ? 'ok' : ($total < 1000 ? 'warning' : 'error'),
            'label' => 'Prune',
            'message' => $total === 0 ? 'Geen achterstallige prune-data gevonden.' : $total . ' oude records wachten op opschonen.',
            'meta' => $pending,
        ];
    }
}
