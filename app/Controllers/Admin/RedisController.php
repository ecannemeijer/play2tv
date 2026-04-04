<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Libraries\SecurityEventService;
use App\Services\RedisService;
use CodeIgniter\Controller;
use Config\Services;
use Throwable;

class RedisController extends Controller
{
    private RedisService $redisService;
    private SecurityEventService $securityEvents;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->redisService = Services::redisAdmin();
        $this->securityEvents = new SecurityEventService();
    }

    public function index()
    {
        $dashboardError = null;
        $snapshot = [
            'overview' => ['status' => 'DISCONNECTED', 'generated_at' => date(DATE_ATOM)],
            'performance' => [],
            'memory' => ['ttl' => []],
            'keys' => ['dbsize' => 0, 'sample_size' => 0, 'sampled_prefixes' => [], 'recent_keys' => []],
            'slowlog' => [],
            'iptv' => ['active_users' => 0, 'active_streams' => 0, 'cache_hits' => ['epg' => 0, 'vod' => 0]],
            'alerts' => [],
        ];

        try {
            $snapshot = $this->redisService->buildDashboardSnapshot();
        } catch (Throwable $exception) {
            $dashboardError = $exception->getMessage();
        }

        $websocketToken = null;
        $websocketError = null;

        try {
            $websocketToken = $this->createWebSocketToken();
        } catch (Throwable $exception) {
            $websocketError = $exception->getMessage();
        }

        return view('admin/redis_dashboard', [
            'title' => 'REDIS - Play2TV Admin',
            'initialSnapshotJson' => json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'initialDataUrl' => base_url('admin/redis/initial'),
            'searchKeysUrl' => base_url('admin/redis/keys'),
            'deleteKeyUrl' => base_url('admin/redis/keys/delete'),
            'flushPrefixUrl' => base_url('admin/redis/admin/flush-prefix'),
            'executeCommandUrl' => base_url('admin/redis/admin/execute'),
            'websocketUrl' => (string) env('redis.websocket.url', 'ws://127.0.0.1:8081'),
            'websocketToken' => $websocketToken,
            'csrfTokenName' => csrf_token(),
            'csrfHash' => csrf_hash(),
            'flushablePrefixes' => $this->redisService->getFlushablePrefixes(),
            'allowedCommands' => $this->redisService->getAllowedCommands(),
            'dashboardError' => $dashboardError,
            'websocketError' => $websocketError,
        ]);
    }

    public function getInitialData()
    {
        try {
            return $this->response->setJSON($this->successResponse([
                'snapshot' => $this->redisService->buildDashboardSnapshot(),
            ]));
        } catch (Throwable $exception) {
            return $this->response->setStatusCode(503)->setJSON($this->errorResponse($exception->getMessage()));
        }
    }

    public function searchKeys()
    {
        $pattern = (string) ($this->request->getGet('pattern') ?? '*');

        try {
            return $this->response->setJSON($this->successResponse([
                'keys' => $this->redisService->scanKeys($pattern),
            ]));
        } catch (Throwable $exception) {
            return $this->response->setStatusCode(503)->setJSON($this->errorResponse($exception->getMessage()));
        }
    }

    public function deleteKey()
    {
        $key = trim((string) $this->request->getPost('key'));
        if ($key === '') {
            return $this->response->setStatusCode(422)->setJSON($this->errorResponse('Key is required.'));
        }

        try {
            $deleted = $this->redisService->deleteKey($key);
            $this->logAdminAction('redis.key.delete', ['key' => $key, 'deleted' => $deleted]);

            return $this->response->setJSON($this->successResponse([
                'deleted' => $deleted,
            ]));
        } catch (Throwable $exception) {
            return $this->response->setStatusCode(503)->setJSON($this->errorResponse($exception->getMessage()));
        }
    }

    public function flushPrefix()
    {
        $prefix = trim((string) $this->request->getPost('prefix'));
        if ($prefix === '') {
            return $this->response->setStatusCode(422)->setJSON($this->errorResponse('Prefix is required.'));
        }

        try {
            $result = $this->redisService->flushPrefix($prefix);
            $this->logAdminAction('redis.prefix.flush', $result);

            return $this->response->setJSON($this->successResponse([
                'flush' => $result,
            ]));
        } catch (Throwable $exception) {
            return $this->response->setStatusCode(503)->setJSON($this->errorResponse($exception->getMessage()));
        }
    }

    public function execute()
    {
        $command = trim((string) $this->request->getPost('command'));
        if ($command === '') {
            return $this->response->setStatusCode(422)->setJSON($this->errorResponse('Command is required.'));
        }

        try {
            $result = $this->redisService->execute($command);
            $this->logAdminAction('redis.command.execute', ['command' => $command]);

            return $this->response->setJSON($this->successResponse([
                'execution' => $result,
            ]));
        } catch (Throwable $exception) {
            return $this->response->setStatusCode(503)->setJSON($this->errorResponse($exception->getMessage()));
        }
    }

    private function successResponse(array $data): array
    {
        return $data + [
            'csrf' => [
                'name' => csrf_token(),
                'hash' => csrf_hash(),
            ],
        ];
    }

    private function errorResponse(string $message): array
    {
        return [
            'error' => $message,
            'csrf' => [
                'name' => csrf_token(),
                'hash' => csrf_hash(),
            ],
        ];
    }

    private function createWebSocketToken(): string
    {
        $secret = trim((string) env('redis.websocket.secret', ''));
        if ($secret === '' || $secret === 'CHANGE_ME_REDIS_ADMIN_SECRET') {
            throw new \RuntimeException('redis.websocket.secret must be configured before using the Redis dashboard.');
        }

        $payload = [
            'admin_id' => (int) session()->get('admin_id'),
            'username' => (string) session()->get('admin_username'),
            'issued_at' => time(),
            'expires_at' => time() + 300,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $json, $secret);

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=') . '.' . $signature;
    }

    private function logAdminAction(string $eventType, array $context): void
    {
        $context['admin_id'] = (int) session()->get('admin_id');
        $context['admin_username'] = (string) session()->get('admin_username');
        $this->securityEvents->log($eventType, 'warning', $this->request, null, $context);
    }
}