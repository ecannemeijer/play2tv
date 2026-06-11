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
 * Routes (behind /admin prefix, protected by AdminAuthFilter):
 *   GET  /admin/cloudflare       → Main dashboard
 *   POST /admin/cloudflare/fetch → Trigger a fresh Cloudflare API fetch
 */
class CloudflareController extends Controller
{
    private const CF_BASE = 'https://api.cloudflare.com/client/v4';

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
    // Calls Cloudflare API and stores snapshot
    // ─────────────────────────────────────────────────────────────────────────
    public function fetch()
    {
        $accountId = env('cloudflare.accountId');
        $apiToken  = env('cloudflare.apiToken');

        if (empty($accountId) || empty($apiToken)) {
            return redirect()->back()->with('error', 'Cloudflare API credentials not configured in .env');
        }

        // Resolve zone IDs: prefer .env (bypasses IP-restricted zone listing)
        $zones = $this->resolveZones();

        if (empty($zones)) {
            return redirect()->back()->with('error',
                'Geen zone IDs gevonden. '
                . 'Voeg je Zone ID toe aan cloudflare.zoneIds in .env. '
                . 'Te vinden in Cloudflare Dashboard → Websites → jouwdomein.com → Overzicht → Zone ID (rechtsonder). '
                . 'De /zones API listing is geblokkeerd door IP filtering op je API token (error 9109).'
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

        foreach ($zones as $zoneEntry) {
            $zoneId   = $zoneEntry['id'] ?? '';
            $zoneName = $zoneEntry['name'] ?? $zoneId;

            if (empty($zoneId)) continue;

            // Try to resolve zone name from API
            $zoneInfo = $this->cfRequest('GET', "/zones/{$zoneId}");
            $resolvedName = $zoneInfo['result']['name'] ?? $zoneName;
            $zoneNames[]  = $resolvedName;

            // Get analytics dashboard (last 24h)
            $analytics = $this->cfRequest('GET', "/zones/{$zoneId}/analytics/dashboard", [
                'since' => '-1440', // last 24 hours
            ]);

            if (! empty($analytics['result'])) {
                $totals = $analytics['result']['totals'] ?? [];
                $aggRequests  += (int) ($totals['requests']['all'] ?? 0);
                $aggCached    += (int) ($totals['requests']['cached'] ?? 0);
                $aggUncached  += (int) ($totals['requests']['uncached'] ?? 0);
                $aggBandwidth += (int) ($totals['bandwidth']['all'] ?? 0);
                $aggPageViews += (int) ($totals['pageviews']['all'] ?? 0);
                $aggVisitors  += (int) ($totals['uniques']['all'] ?? 0);
                $aggThreats   += (int) ($totals['threats']['all'] ?? 0);

                // Collect country data
                $countries = $analytics['result']['totals']['country_map'] ?? [];
                foreach ($countries as $code => $count) {
                    $allCountries[$code] = ($allCountries[$code] ?? 0) + (int) $count;
                }

                // Collect status codes
                $statuses = $analytics['result']['totals']['response_status_map'] ?? [];
                foreach ($statuses as $code => $count) {
                    $allStatuses[$code] = ($allStatuses[$code] ?? 0) + (int) $count;
                }

                // Collect browser data
                $browsers = $analytics['result']['totals']['client_http_map'] ?? [];
                foreach ($browsers as $browser => $count) {
                    $allBrowsers[$browser] = ($allBrowsers[$browser] ?? 0) + (int) $count;
                }
            }

            // Get bot analytics separately
            $bots = $this->cfRequest('GET', "/zones/{$zoneId}/analytics/dashboard", [
                'since'        => '-1440',
                'bot_class'    => 'bot',
            ]);
            $aggBots += (int) ($bots['result']['totals']['requests']['all'] ?? 0);

            // Track per-subdomain
            $allSubdomains[$resolvedName] = ($allSubdomains[$resolvedName] ?? 0) + (int) ($totals['requests']['all'] ?? 0);

            $stored++;
        }

        // Save snapshot to database (one row per day)
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
            ->with('success', "Data opgehaald voor {$stored} zone(s). Totaal {$aggPageViews} pageviews, {$aggThreats} threats geblokkeerd.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resolve zone IDs: from .env (cloudflare.zoneIds) or fallback to API list
    //
    // IMPORTANT: If your API token has Client IP Address Filtering, the zone
    // listing endpoint (/zones) returns error 9109 "Cannot use the access
    // token from location". In that case you MUST set cloudflare.zoneIds.
    //
    // Find your Zone ID at:
    //   Cloudflare Dashboard → Websites → yourdomain.com → Overview → Zone ID (right sidebar)
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

        // Fallback: list zones from API (fails with error 9109 if IP-restricted)
        $response = $this->cfRequest('GET', '/zones', [
            'per_page' => '50',
        ]);

        if (! empty($response['result']) && is_array($response['result'])) {
            return $response['result'];
        }

        return [];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: make a Cloudflare API request via cURL
    // ─────────────────────────────────────────────────────────────────────────
    private function cfRequest(string $method, string $path, array $params = []): array
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
            log_message('error', 'Cloudflare API unreachable: ' . $path);
            return [];
        }

        $data = json_decode($response, true);
        if (! is_array($data)) {
            log_message('error', 'Cloudflare API invalid JSON: ' . $path);
            return [];
        }

        if ($httpCode >= 400) {
            log_message('error', "Cloudflare API error {$httpCode}: " . json_encode($data));
        }

        return $data;
    }
}