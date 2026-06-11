<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Controller;

/**
 * CloudflareController (Admin)
 *
 * Displays a rich analytics dashboard with Cloudflare security & traffic data.
 * Stores daily snapshots in the cloudflare_analytics table.
 *
 * Uses Cloudflare GraphQL Analytics API (account-owned tokens are not
 * supported by the deprecated REST Zone Analytics API — error 1016).
 *
 * Routes (behind /admin prefix, protected by AdminAuthFilter):
 *   GET  /admin/cloudflare       → Main dashboard
 *   POST /admin/cloudflare/fetch → Trigger a fresh Cloudflare API fetch
 */
class CloudflareController extends Controller
{
    private const CF_BASE     = 'https://api.cloudflare.com/client/v4';
    private const CF_GRAPHQL  = 'https://api.cloudflare.com/client/v4/graphql';

    public function __construct()
    {
        helper(['url', 'number']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/cloudflare
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $db = db_connect();

        // Get last 30 days of snapshots
        $snapshots = $db->table('cloudflare_analytics')
            ->orderBy('snapshot_date', 'DESC')
            ->limit(30)
            ->get()
            ->getResultArray();

        // For the chart, reverse so chronologically ordered
        $chartSnapshots = array_reverse($snapshots);

        // Latest snapshot for summary cards
        $latest = $snapshots[0] ?? null;

        // Aggregate totals
        $totals = $db->table('cloudflare_analytics')
            ->select([
                'SUM(total_requests) AS total_requests',
                'SUM(page_views) AS page_views',
                'SUM(unique_visitors) AS unique_visitors',
                'SUM(bandwidth_bytes) AS bandwidth_bytes',
                'SUM(threats_blocked) AS threats_blocked',
                'SUM(bot_requests) AS bot_requests',
                'SUM(cached_requests) AS cached_requests',
                'SUM(uncached_requests) AS uncached_requests',
            ])
            ->get()
            ->getRowArray();

        $cacheHitRate = 0;
        $totalCache   = (int) ($totals['cached_requests'] ?? 0) + (int) ($totals['uncached_requests'] ?? 0);
        if ($totalCache > 0) {
            $cacheHitRate = round(((int) ($totals['cached_requests'] ?? 0) / $totalCache) * 100, 1);
        }

        // Collect country data across all snapshots
        $countryTotals = [];
        foreach ($snapshots as $s) {
            if (empty($s['countries_data'])) continue;
            $data = json_decode((string) $s['countries_data'], true);
            if (! is_array($data)) continue;
            foreach ($data as $country => $count) {
                $countryTotals[$country] = ($countryTotals[$country] ?? 0) + (int) $count;
            }
        }
        arsort($countryTotals);
        $topCountries = array_slice($countryTotals, 0, 15);

        // Collect browser data
        $browserTotals = [];
        foreach ($snapshots as $s) {
            if (empty($s['browser_data'])) continue;
            $data = json_decode((string) $s['browser_data'], true);
            if (! is_array($data)) continue;
            foreach ($data as $browser => $count) {
                $browserTotals[$browser] = ($browserTotals[$browser] ?? 0) + (int) $count;
            }
        }
        arsort($browserTotals);
        $topBrowsers = array_slice($browserTotals, 0, 10);

        // Collect subdomain data
        $subdomainTotals = [];
        foreach ($snapshots as $s) {
            if (empty($s['subdomain_data'])) continue;
            $data = json_decode((string) $s['subdomain_data'], true);
            if (! is_array($data)) continue;
            foreach ($data as $sub => $count) {
                $subdomainTotals[$sub] = ($subdomainTotals[$sub] ?? 0) + (int) $count;
            }
        }
        arsort($subdomainTotals);

        // Prepare chart data (last 30 days)
        $chartLabels     = [];
        $chartPageViews  = [];
        $chartVisitors   = [];
        $chartRequests   = [];
        $chartThreats    = [];
        $chartBandwidth  = [];

        foreach ($chartSnapshots as $s) {
            $chartLabels[]    = $s['snapshot_date'];
            $chartPageViews[] = (int) ($s['page_views'] ?? 0);
            $chartVisitors[]  = (int) ($s['unique_visitors'] ?? 0);
            $chartRequests[]  = (int) ($s['total_requests'] ?? 0);
            $chartThreats[]   = (int) ($s['threats_blocked'] ?? 0);
            $chartBandwidth[] = round(((int) ($s['bandwidth_bytes'] ?? 0)) / (1024 * 1024), 2); // MB
        }

        return view('admin/cloudflare/index', [
            'title'          => 'Cloudflare Analytics — Play2TV Admin',
            'latest'         => $latest,
            'totals'         => $totals,
            'cacheHitRate'   => $cacheHitRate,
            'snapshots'      => $snapshots,
            'topCountries'   => $topCountries,
            'topBrowsers'    => $topBrowsers,
            'subdomainTotals'=> $subdomainTotals,
            'chartLabels'    => $chartLabels,
            'chartPageViews' => $chartPageViews,
            'chartVisitors'  => $chartVisitors,
            'chartRequests'  => $chartRequests,
            'chartThreats'   => $chartThreats,
            'chartBandwidth' => $chartBandwidth,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/cloudflare/fetch
    // Uses Cloudflare GraphQL Analytics API to fetch data
    // ─────────────────────────────────────────────────────────────────────────
    public function fetch()
    {
        $accountId = env('cloudflare.accountId');
        $apiToken  = env('cloudflare.apiToken');

        if (empty($accountId) || empty($apiToken)) {
            return redirect()->back()->with('error', 'Cloudflare API credentials not configured in .env');
        }

        // Resolve zone IDs from .env
        $zones = $this->resolveZones();

        if (empty($zones)) {
            return redirect()->back()->with('error',
                'Geen zone IDs gevonden. '
                . 'Voeg je Zone ID toe aan cloudflare.zoneIds in .env. '
                . 'Te vinden in Cloudflare Dashboard → Websites → jouwdomein.com → Overzicht → Zone ID (rechtsonder).'
            );
        }

        $db    = db_connect();
        $today = date('Y-m-d');
        $stored = 0;

        // Track aggregates across zones for this snapshot
        $aggRequests     = 0;
        $aggCached       = 0;
        $aggUncached     = 0;
        $aggBandwidth    = 0;
        $aggPageViews    = 0;
        $aggVisitors     = 0;
        $aggThreats      = 0;
        $aggBots         = 0;
        $allCountries    = [];
        $allStatuses     = [];
        $allBrowsers     = [];
        $allSubdomains   = [];
        $zoneNames       = [];

        // Fetch last 30 days of data via GraphQL
        $dateSince = date('Y-m-d', strtotime('-30 days'));

        foreach ($zones as $zoneEntry) {
            $zoneId   = $zoneEntry['id'] ?? '';
            $zoneName = $zoneEntry['name'] ?? $zoneId;

            if (empty($zoneId)) continue;

            // Resolve zone name via REST API
            $zoneInfo = $this->restRequest('GET', "/zones/{$zoneId}");
            $resolvedName = $zoneInfo['result']['name'] ?? $zoneName;
            $zoneNames[]  = $resolvedName;

            // Fetch analytics via GraphQL
            $analyticsData = $this->fetchGraphQLAnalytics($zoneId, $dateSince);

            if ($analyticsData !== null) {
                $aggRequests  += $analyticsData['totalRequests'];
                $aggCached    += $analyticsData['cachedRequests'];
                $aggUncached  += ($analyticsData['totalRequests'] - $analyticsData['cachedRequests']);
                $aggBandwidth += $analyticsData['bandwidthBytes'];
                $aggPageViews += $analyticsData['pageViews'];
                $aggVisitors  += $analyticsData['uniqueVisitors'];
                $aggThreats   += $analyticsData['threats'];
                $aggBots      += $analyticsData['botRequests'];

                // Merge country data
                foreach ($analyticsData['countries'] as $country => $count) {
                    $allCountries[$country] = ($allCountries[$country] ?? 0) + $count;
                }

                // Merge status data
                foreach ($analyticsData['statusCodes'] as $code => $count) {
                    $allStatuses[$code] = ($allStatuses[$code] ?? 0) + $count;
                }

                // Merge browser data
                foreach ($analyticsData['browsers'] as $browser => $count) {
                    $allBrowsers[$browser] = ($allBrowsers[$browser] ?? 0) + $count;
                }

                // Track per-zone
                $allSubdomains[$resolvedName] = ($allSubdomains[$resolvedName] ?? 0) + $analyticsData['totalRequests'];
            }

            $stored++;
        }

        // Save snapshot to database
        arsort($allCountries);
        arsort($allBrowsers);

        $zoneNameStr = ! empty($zoneNames) ? implode(', ', $zoneNames) : (count($zones) . ' zones');

        // Check if a snapshot for today already exists
        $existing = $db->table('cloudflare_analytics')
            ->where('snapshot_date', $today)
            ->get()
            ->getRowArray();

        $snapshotData = [
            'snapshot_date'     => $today,
            'zone_id'           => $accountId,
            'zone_name'         => $zoneNameStr,
            'total_requests'    => $aggRequests,
            'cached_requests'   => $aggCached,
            'uncached_requests' => $aggUncached,
            'bandwidth_bytes'   => $aggBandwidth,
            'page_views'        => $aggPageViews,
            'unique_visitors'   => $aggVisitors,
            'threats_blocked'   => $aggThreats,
            'bot_requests'      => $aggBots,
            'countries_data'    => ! empty($allCountries) ? json_encode($allCountries) : null,
            'http_status_data'  => ! empty($allStatuses) ? json_encode($allStatuses) : null,
            'browser_data'      => ! empty($allBrowsers) ? json_encode($allBrowsers) : null,
            'subdomain_data'    => ! empty($allSubdomains) ? json_encode($allSubdomains) : null,
            'created_at'        => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $db->table('cloudflare_analytics')
                ->where('id', $existing['id'])
                ->update($snapshotData);
        } else {
            $db->table('cloudflare_analytics')->insert($snapshotData);
        }

        return redirect()->to(base_url('admin/cloudflare'))
            ->with('success', "Data opgehaald voor {$stored} zone(s) via GraphQL. Totaal {$aggPageViews} pageviews, {$aggThreats} threats geblokkeerd.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Fetch analytics data via Cloudflare GraphQL API
    // ─────────────────────────────────────────────────────────────────────────
    private function fetchGraphQLAnalytics(string $zoneId, string $dateSince): ?array
    {
        // GraphQL query: get last 30 days of daily aggregates
        $query = <<<GRAPHQL
        {
          viewer {
            zones(filter: { zoneTag: "$zoneId" }) {
              httpRequests1dGroups(
                limit: 30,
                filter: { date_gt: "$dateSince" }
              ) {
                sum {
                  requests
                  pageViews
                  threats
                  bytes
                  cachedRequests
                  uniqueVisitors
                }
                dimensions {
                  date
                }
              }
            }
          }
        }
        GRAPHQL;

        $dailyData = $this->graphqlRequest($query);

        if ($dailyData === null) {
            return null;
        }

        $groups = $dailyData['data']['viewer']['zones'][0]['httpRequests1dGroups'] ?? [];

        // Aggregate all daily sums
        $totalRequests   = 0;
        $totalPageViews  = 0;
        $totalThreats    = 0;
        $totalBytes      = 0;
        $totalCached     = 0;
        $totalVisitors   = 0;

        foreach ($groups as $group) {
            $sum = $group['sum'] ?? [];
            $totalRequests  += (int) ($sum['requests'] ?? 0);
            $totalPageViews += (int) ($sum['pageViews'] ?? 0);
            $totalThreats   += (int) ($sum['threats'] ?? 0);
            $totalBytes     += (int) ($sum['bytes'] ?? 0);
            $totalCached    += (int) ($sum['cachedRequests'] ?? 0);
            $totalVisitors  += (int) ($sum['uniqueVisitors'] ?? 0);
        }

        // Now get detailed breakdowns: countries, status codes, browsers
        $detailQuery = <<<GRAPHQL
        {
          viewer {
            zones(filter: { zoneTag: "$zoneId" }) {
              httpRequests1dGroups(
                limit: 1,
                filter: { date_gt: "$dateSince" }
              ) {
                sum {
                  countryMap { clientCountryName requests }
                  responseStatusMap { edgeResponseStatus requests }
                  browserMap { uaBrowserFamily requests }
                  botScoreGroups { botScore requests }
                  clientRequestHTTPHostMap { clientRequestHTTPHost requests }
                }
              }
            }
          }
        }
        GRAPHQL;

        $detailData = $this->graphqlRequest($detailQuery);
        $detailSum  = $detailData['data']['viewer']['zones'][0]['httpRequests1dGroups'][0]['sum'] ?? [];

        // Parse country data
        $countries = [];
        foreach ($detailSum['countryMap'] ?? [] as $item) {
            $name = $item['clientCountryName'] ?? 'Unknown';
            $countries[$name] = ($countries[$name] ?? 0) + (int) ($item['requests'] ?? 0);
        }
        arsort($countries);

        // Parse status codes
        $statusCodes = [];
        foreach ($detailSum['responseStatusMap'] ?? [] as $item) {
            $code = (string) ($item['edgeResponseStatus'] ?? '0');
            $statusCodes[$code] = ($statusCodes[$code] ?? 0) + (int) ($item['requests'] ?? 0);
        }
        arsort($statusCodes);

        // Parse browser data
        $browsers = [];
        foreach ($detailSum['browserMap'] ?? [] as $item) {
            $name = $item['uaBrowserFamily'] ?? 'Unknown';
            $browsers[$name] = ($browsers[$name] ?? 0) + (int) ($item['requests'] ?? 0);
        }
        arsort($browsers);

        // Bot traffic: sum up requests with botScore >= 30 (Cloudflare's bot threshold)
        $botRequests = 0;
        foreach ($detailSum['botScoreGroups'] ?? [] as $item) {
            $score = (int) ($item['botScore'] ?? 0);
            if ($score >= 30) {
                $botRequests += (int) ($item['requests'] ?? 0);
            }
        }

        return [
            'totalRequests'  => $totalRequests,
            'cachedRequests' => $totalCached,
            'bandwidthBytes' => $totalBytes,
            'pageViews'      => $totalPageViews,
            'uniqueVisitors' => $totalVisitors,
            'threats'        => $totalThreats,
            'botRequests'    => $botRequests,
            'countries'      => $countries,
            'statusCodes'    => $statusCodes,
            'browsers'       => $browsers,
            'subdomains'     => [],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resolve zone IDs: from .env (cloudflare.zoneIds) or fallback
    // ─────────────────────────────────────────────────────────────────────────
    private function resolveZones(): array
    {
        $envZoneIds = env('cloudflare.zoneIds', '');
        if (! empty($envZoneIds)) {
            $ids   = array_map('trim', explode(',', $envZoneIds));
            $zones = [];
            foreach ($ids as $id) {
                if ($id !== '') {
                    $zones[] = ['id' => $id, 'name' => $id];
                }
            }
            return $zones;
        }

        return [];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: Cloudflare GraphQL request
    // ─────────────────────────────────────────────────────────────────────────
    private function graphqlRequest(string $query): ?array
    {
        $apiToken = env('cloudflare.apiToken');

        $ch = curl_init(self::CF_GRAPHQL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiToken,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode(['query' => $query]),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode === 0) {
            log_message('error', 'Cloudflare GraphQL unreachable: ' . ($curlError ?: 'timeout'));
            return null;
        }

        $data = json_decode($response, true);
        if (! is_array($data)) {
            log_message('error', 'Cloudflare GraphQL invalid JSON');
            return null;
        }

        if (! empty($data['errors'])) {
            log_message('error', 'Cloudflare GraphQL errors: ' . json_encode($data['errors']));
            return null;
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: Cloudflare REST request (for zone info only)
    // ─────────────────────────────────────────────────────────────────────────
    private function restRequest(string $method, string $path, array $params = []): array
    {
        $apiToken = env('cloudflare.apiToken');
        $url = self::CF_BASE . $path;

        if (! empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiToken,
                'Content-Type: application/json',
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode === 0) {
            return [];
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : [];
    }
}