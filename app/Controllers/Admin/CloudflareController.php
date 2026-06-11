<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Controller;

class CloudflareController extends Controller
{
    private const CF_BASE    = 'https://api.cloudflare.com/client/v4';
    private const CF_GRAPHQL = 'https://api.cloudflare.com/client/v4/graphql';

    public function __construct()
    {
        helper(['url', 'number']);
    }

    public function index()
    {
        $db = db_connect();
        $snapshots = $db->table('cloudflare_analytics')
            ->orderBy('snapshot_date', 'DESC')->limit(30)->get()->getResultArray();

        $chartSnapshots = array_reverse($snapshots);
        $latest = $snapshots[0] ?? null;

        $totals = $db->table('cloudflare_analytics')->select([
            'SUM(total_requests) AS total_requests',
            'SUM(page_views) AS page_views',
            'SUM(unique_visitors) AS unique_visitors',
            'SUM(bandwidth_bytes) AS bandwidth_bytes',
            'SUM(threats_blocked) AS threats_blocked',
            'SUM(bot_requests) AS bot_requests',
            'SUM(cached_requests) AS cached_requests',
            'SUM(uncached_requests) AS uncached_requests',
        ])->get()->getRowArray();

        $cacheHitRate = 0;
        $totalCache = (int) ($totals['cached_requests'] ?? 0) + (int) ($totals['uncached_requests'] ?? 0);
        if ($totalCache > 0) {
            $cacheHitRate = round(((int) ($totals['cached_requests'] ?? 0) / $totalCache) * 100, 1);
        }

        $countryTotals = [];
        foreach ($snapshots as $s) {
            if (empty($s['countries_data'])) continue;
            $data = json_decode((string) $s['countries_data'], true);
            if (! is_array($data)) continue;
            foreach ($data as $c => $n) {
                $countryTotals[$c] = ($countryTotals[$c] ?? 0) + (int) $n;
            }
        }
        arsort($countryTotals);
        $topCountries = array_slice($countryTotals, 0, 15);

        $browserTotals = [];
        foreach ($snapshots as $s) {
            if (empty($s['browser_data'])) continue;
            $data = json_decode((string) $s['browser_data'], true);
            if (! is_array($data)) continue;
            foreach ($data as $b => $n) {
                $browserTotals[$b] = ($browserTotals[$b] ?? 0) + (int) $n;
            }
        }
        arsort($browserTotals);
        $topBrowsers = array_slice($browserTotals, 0, 10);

        $subdomainTotals = [];
        foreach ($snapshots as $s) {
            if (empty($s['subdomain_data'])) continue;
            $data = json_decode((string) $s['subdomain_data'], true);
            if (! is_array($data)) continue;
            foreach ($data as $sub => $n) {
                $subdomainTotals[$sub] = ($subdomainTotals[$sub] ?? 0) + (int) $n;
            }
        }
        arsort($subdomainTotals);

        $chartLabels = $chartPageViews = $chartVisitors = $chartRequests = $chartThreats = $chartBandwidth = [];
        foreach ($chartSnapshots as $s) {
            $chartLabels[]    = $s['snapshot_date'];
            $chartPageViews[] = (int) ($s['page_views'] ?? 0);
            $chartVisitors[]  = (int) ($s['unique_visitors'] ?? 0);
            $chartRequests[]  = (int) ($s['total_requests'] ?? 0);
            $chartThreats[]   = (int) ($s['threats_blocked'] ?? 0);
            $chartBandwidth[] = round(((int) ($s['bandwidth_bytes'] ?? 0)) / (1024 * 1024), 2);
        }

        return view('admin/cloudflare/index', [
            'title' => 'Cloudflare Analytics — Play2TV Admin',
            'latest' => $latest, 'totals' => $totals, 'cacheHitRate' => $cacheHitRate,
            'snapshots' => $snapshots, 'topCountries' => $topCountries,
            'topBrowsers' => $topBrowsers, 'subdomainTotals' => $subdomainTotals,
            'chartLabels' => $chartLabels, 'chartPageViews' => $chartPageViews,
            'chartVisitors' => $chartVisitors, 'chartRequests' => $chartRequests,
            'chartThreats' => $chartThreats, 'chartBandwidth' => $chartBandwidth,
        ]);
    }

    public function fetch()
    {
        $accountId = env('cloudflare.accountId');
        $apiToken  = env('cloudflare.apiToken');
        if (empty($accountId) || empty($apiToken)) {
            return redirect()->back()->with('error', 'Cloudflare API credentials not configured in .env');
        }

        $zones = $this->resolveZones();
        if (empty($zones)) {
            return redirect()->back()->with('error', 'Geen zone IDs gevonden. Voeg cloudflare.zoneIds toe in .env.');
        }

        $db = db_connect();
        $today = date('Y-m-d');
        $stored = $errors = [];
        $dateSince = date('Y-m-d', strtotime('-30 days'));

        $agg = ['requests' => 0, 'cached' => 0, 'bandwidth' => 0, 'pageViews' => 0,
                'threats' => 0, 'bots' => 0];
        $allCountries = $allStatuses = $allBrowsers = $allSubdomains = $zoneNames = [];

        foreach ($zones as $ze) {
            $zoneId = $ze['id'] ?? '';
            if (empty($zoneId)) continue;

            $zoneInfo = $this->restGet("/zones/{$zoneId}");
            $zName = $zoneInfo['result']['name'] ?? $ze['name'] ?? $zoneId;
            $zoneNames[] = $zName;

            $data = $this->fetchAnalytics($zoneId, $dateSince);
            if ($data === null) {
                $errors[] = "Zone {$zName}: fetchAnalytics returned null (unexpected)";
                continue;
            }
            if (! empty($data['error'])) {
                $errors[] = "Zone {$zName}: {$data['error']}";
                continue;
            }

            $agg['requests']  += $data['requests'];
            $agg['cached']    += $data['cached'];
            $agg['bandwidth'] += $data['bandwidth'];
            $agg['pageViews'] += $data['pageViews'];
            $agg['threats']   += $data['threats'];
            $agg['bots']      += $data['bots'];

            foreach ($data['countries'] as $c => $n) {
                $allCountries[$c] = ($allCountries[$c] ?? 0) + $n;
            }
            foreach ($data['statusCodes'] as $c => $n) {
                $allStatuses[$c] = ($allStatuses[$c] ?? 0) + $n;
            }
            foreach ($data['browsers'] as $b => $n) {
                $allBrowsers[$b] = ($allBrowsers[$b] ?? 0) + $n;
            }
            $allSubdomains[$zName] = ($allSubdomains[$zName] ?? 0) + $data['requests'];
            $stored[] = $zName;
        }

        if (empty($stored) && ! empty($errors)) {
            return redirect()->back()->with('error', implode(' | ', $errors));
        }

        arsort($allCountries);
        arsort($allBrowsers);
        $zNameStr = implode(', ', $zoneNames);

        $existing = $db->table('cloudflare_analytics')->where('snapshot_date', $today)->get()->getRowArray();
        $row = [
            'snapshot_date' => $today, 'zone_id' => $accountId, 'zone_name' => $zNameStr,
            'total_requests' => $agg['requests'], 'cached_requests' => $agg['cached'],
            'uncached_requests' => $agg['requests'] - $agg['cached'],
            'bandwidth_bytes' => $agg['bandwidth'], 'page_views' => $agg['pageViews'],
            'unique_visitors' => 0, 'threats_blocked' => $agg['threats'],
            'bot_requests' => $agg['bots'],
            'countries_data' => ! empty($allCountries) ? json_encode($allCountries) : null,
            'http_status_data' => ! empty($allStatuses) ? json_encode($allStatuses) : null,
            'browser_data' => ! empty($allBrowsers) ? json_encode($allBrowsers) : null,
            'subdomain_data' => ! empty($allSubdomains) ? json_encode($allSubdomains) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $db->table('cloudflare_analytics')->where('id', $existing['id'])->update($row);
        } else {
            $db->table('cloudflare_analytics')->insert($row);
        }

        $msg = "Data opgehaald voor " . count($stored) . " zone(s): {$zNameStr}. {$agg['pageViews']} pageviews, {$agg['threats']} threats.";
        if (! empty($errors)) $msg .= ' (' . implode('; ', $errors) . ')';
        return redirect()->to(base_url('admin/cloudflare'))->with('success', $msg);
    }

    private function fetchAnalytics(string $zoneId, string $dateSince): ?array
    {
        // Aggregate query
        $q1 = json_encode(['query' =>
            '{ viewer { zones(filter: { zoneTag: "' . $zoneId . '" }) {'
            . ' httpRequests1dGroups(limit: 30, filter: { date_gt: "' . $dateSince . '" }) {'
            . ' sum { requests pageViews threats bytes cachedRequests } dimensions { date } } } } }'
        ]);
        $r1 = $this->graphqlCall($q1);
        if (! empty($r1['_error'])) return ['error' => $r1['_error']];

        $groups = $r1['data']['viewer']['zones'][0]['httpRequests1dGroups'] ?? [];
        $req = $pv = $threats = $bytes = $cached = 0;
        foreach ($groups as $g) {
            $s = $g['sum'] ?? [];
            $req     += (int) ($s['requests'] ?? 0);
            $pv      += (int) ($s['pageViews'] ?? 0);
            $threats += (int) ($s['threats'] ?? 0);
            $bytes   += (int) ($s['bytes'] ?? 0);
            $cached  += (int) ($s['cachedRequests'] ?? 0);
        }

        // Country detail query
        $q2 = json_encode(['query' =>
            '{ viewer { zones(filter: { zoneTag: "' . $zoneId . '" }) {'
            . ' httpRequests1dGroups(limit: 1, filter: { date_gt: "' . $dateSince . '" }) {'
            . ' sum { countryMap { clientCountryName requests } } } } } }'
        ]);
        $r2 = $this->graphqlCall($q2);
        $detailSum = [];
        if (empty($r2['_error'])) {
            $detailSum = $r2['data']['viewer']['zones'][0]['httpRequests1dGroups'][0]['sum'] ?? [];
        }

        $countries = [];
        foreach ($detailSum['countryMap'] ?? [] as $item) {
            $name = $item['clientCountryName'] ?? 'Unknown';
            $countries[$name] = ($countries[$name] ?? 0) + (int) ($item['requests'] ?? 0);
        }
        arsort($countries);

        return [
            'requests' => $req, 'cached' => $cached, 'bandwidth' => $bytes,
            'pageViews' => $pv, 'threats' => $threats, 'bots' => 0,
            'countries' => $countries, 'statusCodes' => [], 'browsers' => [],
        ];
    }

    private function resolveZones(): array
    {
        $env = env('cloudflare.zoneIds', '');
        if (empty($env)) return [];
        $zones = [];
        foreach (array_map('trim', explode(',', $env)) as $id) {
            if ($id !== '') $zones[] = ['id' => $id, 'name' => $id];
        }
        return $zones;
    }

    private function graphqlCall(string $jsonBody): array
    {
        if (! function_exists('curl_init')) {
            return ['_error' => 'PHP cURL extension is not installed/enabled'];
        }

        $apiToken = env('cloudflare.apiToken');
        if (empty($apiToken)) {
            return ['_error' => 'cloudflare.apiToken is empty or not found in .env — check .env file on this server'];
        }

        // Debug: show token prefix to confirm it's being read
        $tokenPrefix = substr($apiToken, 0, 10);
        $tokenDebug = "token prefix: {$tokenPrefix}..., length: " . strlen($apiToken);
        log_message('debug', 'CF GraphQL auth: ' . $tokenDebug);

        $ch = curl_init(self::CF_GRAPHQL);
        if ($ch === false) {
            return ['_error' => 'curl_init failed — possibly URL blocked or libcurl issue'];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiToken,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => $jsonBody,
        ]);

        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            return ['_error' => 'cURL error: ' . ($curlErr ?: 'timeout')];
        }

        if ($httpCode >= 400) {
            $body = substr($resp, 0, 500);
            return ['_error' => "HTTP {$httpCode} — body: {$body}"];
        }

        $data = json_decode($resp, true);
        if (! is_array($data)) {
            return ['_error' => 'Invalid JSON response: ' . substr($resp, 0, 200)];
        }

        if (! empty($data['errors'])) {
            return ['_error' => 'GraphQL errors: ' . json_encode($data['errors'])];
        }

        return $data;
    }

    private function restGet(string $path): array
    {
        if (! function_exists('curl_init')) return [];
        $ch = curl_init(self::CF_BASE . $path);
        if ($ch === false) return [];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . env('cloudflare.apiToken'), 'Content-Type: application/json'],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        if ($resp === false) return [];
        $data = json_decode($resp, true);
        return is_array($data) ? $data : [];
    }
}