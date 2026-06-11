<?= $this->extend('admin/layout') ?>

<?= $this->section('head') ?>
<style>
    .cf-stat-card {
        background: linear-gradient(135deg, #1e1b4b, #1a1a2e);
        border: 1px solid #2d2d44;
        border-radius: 12px;
        padding: 1.5rem;
        transition: transform .2s;
    }
    .cf-stat-card:hover { transform: translateY(-2px); }
    .cf-stat-value { font-size: 2rem; font-weight: 800; }
    .cf-stat-label { color: #94a3b8; font-size: .85rem; margin-top: .25rem; }
    .cf-stat-icon { font-size: 2.2rem; opacity: .35; position: absolute; top: 1rem; right: 1.25rem; }
    .cf-stat-card.purple .cf-stat-value { color: #a78bfa; }
    .cf-stat-card.blue .cf-stat-value { color: #60a5fa; }
    .cf-stat-card.green .cf-stat-value { color: #34d399; }
    .cf-stat-card.red .cf-stat-value { color: #f87171; }
    .cf-stat-card.orange .cf-stat-value { color: #fb923c; }
    .cf-stat-card.teal .cf-stat-value { color: #2dd4bf; }
    .cf-progress { height: 20px; border-radius: 10px; background: #1e1b4b; }
    .cf-progress .progress-bar { border-radius: 10px; }
    .country-bar { display: flex; align-items: center; gap: .75rem; margin-bottom: .5rem; }
    .country-bar .flag { width: 28px; font-size: 1.2rem; text-align: center; }
    .country-bar .name { width: 140px; font-size: .85rem; color: #cbd5e1; }
    .country-bar .bar-wrap { flex: 1; }
    .country-bar .count { width: 80px; text-align: right; font-size: .85rem; color: #94a3b8; }
    .info-box {
        background: linear-gradient(135deg, #16213e, #1a1a2e);
        border: 1px solid #2d2d44;
        border-radius: 12px;
        padding: 1.25rem;
    }
    .info-box h6 { color: #a78bfa; font-size: .95rem; margin-bottom: .5rem; }
    .info-box p { color: #94a3b8; font-size: .82rem; margin: 0; line-height: 1.5; }
    canvas { max-height: 260px; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Fetch Data Button Row -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="text-muted small">
            <i class="bi bi-clock-history me-1"></i>
            Laatste snapshot: <?= esc($latest['snapshot_date'] ?? 'Nog geen data') ?>
            <?php if (! empty($latest['created_at'])): ?>
                om <?= esc(date('H:i', strtotime($latest['created_at']))) ?>
            <?php endif; ?>
        </span>
    </div>
    <form method="post" action="<?= base_url('admin/cloudflare/fetch') ?>" class="d-inline">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-cloud-download me-1"></i> Fetch Cloudflare Data
        </button>
    </form>
</div>

<?php if (empty($snapshots)): ?>
    <!-- Empty State -->
    <div class="text-center py-5">
        <i class="bi bi-cloud-slash" style="font-size: 4rem; color: #4b5563;"></i>
        <h4 class="mt-3">Nog geen Cloudflare data</h4>
        <p class="text-muted">Klik op "Fetch Cloudflare Data" om de eerste snapshot op te halen.</p>
    </div>
<?php else: ?>

<!-- ===== STAT CARDS ROW ===== -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-2">
        <div class="cf-stat-card purple position-relative">
            <i class="bi bi-eye cf-stat-icon"></i>
            <div class="cf-stat-value"><?= number_format((int) ($totals['page_views'] ?? 0)) ?></div>
            <div class="cf-stat-label">Total Pageviews</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="cf-stat-card blue position-relative">
            <i class="bi bi-people cf-stat-icon"></i>
            <div class="cf-stat-value"><?= number_format((int) ($totals['unique_visitors'] ?? 0)) ?></div>
            <div class="cf-stat-label">Unique Visitors</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="cf-stat-card teal position-relative">
            <i class="bi bi-arrow-left-right cf-stat-icon"></i>
            <div class="cf-stat-value" style="font-size:1.6rem;"><?= number_format((int) ($totals['total_requests'] ?? 0)) ?></div>
            <div class="cf-stat-label">Total Requests</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="cf-stat-card orange position-relative">
            <i class="bi bi-hdd-stack cf-stat-icon"></i>
            <div class="cf-stat-value" style="font-size:1.6rem;"><?= esc($cacheHitRate) ?>%</div>
            <div class="cf-stat-label">Cache Hit Ratio</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="cf-stat-card red position-relative">
            <i class="bi bi-shield-exclamation cf-stat-icon"></i>
            <div class="cf-stat-value"><?= number_format((int) ($totals['threats_blocked'] ?? 0)) ?></div>
            <div class="cf-stat-label">Threats Blocked</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="cf-stat-card green position-relative">
            <i class="bi bi-wifi cf-stat-icon"></i>
            <div class="cf-stat-value" style="font-size:1.4rem;"><?= number_format(round(((int) ($totals['bandwidth_bytes'] ?? 0)) / (1024 * 1024 * 1024), 2), 2) ?> GB</div>
            <div class="cf-stat-label">Bandwidth</div>
        </div>
    </div>
</div>

<!-- ===== CHARTS ROW ===== -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up me-1"></i> Traffic Over Time (30 days)</span>
                <span class="text-muted small">Pageviews & Visitors</span>
            </div>
            <div class="card-body">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up me-1"></i> Requests & Threats (30 days)</span>
                <span class="text-muted small">Requests vs Threats Blocked</span>
            </div>
            <div class="card-body">
                <canvas id="requestsThreatsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ===== BANDWIDTH CHART ===== -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-bar-chart me-1"></i> Bandwidth Usage (MB/day)
            </div>
            <div class="card-body">
                <canvas id="bandwidthChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pie-chart me-1"></i> Bot Traffic Analysis
            </div>
            <div class="card-body">
                <canvas id="botChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ===== COUNTRIES & BROWSERS ===== -->
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-globe2 me-1"></i> Top Countries by Traffic
            </div>
            <div class="card-body" style="max-height: 380px; overflow-y: auto;">
                <?php if (empty($topCountries)): ?>
                    <p class="text-muted text-center py-3">Geen landendata beschikbaar.</p>
                <?php else: ?>
                    <?php
                    $maxCountry = max($topCountries) ?: 1;
                    $countryNames = [
                        'US' => 'United States', 'NL' => 'Netherlands', 'DE' => 'Germany',
                        'GB' => 'United Kingdom', 'FR' => 'France', 'BE' => 'Belgium',
                        'CA' => 'Canada', 'AU' => 'Australia', 'BR' => 'Brazil',
                        'IN' => 'India', 'JP' => 'Japan', 'RU' => 'Russia',
                        'CN' => 'China', 'KR' => 'South Korea', 'IT' => 'Italy',
                        'ES' => 'Spain', 'SE' => 'Sweden', 'NO' => 'Norway',
                        'DK' => 'Denmark', 'FI' => 'Finland', 'PL' => 'Poland',
                        'UA' => 'Ukraine', 'TR' => 'Turkey', 'SG' => 'Singapore',
                    ];
                    $flags = [
                        'US' => '🇺🇸', 'NL' => '🇳🇱', 'DE' => '🇩🇪', 'GB' => '🇬🇧',
                        'FR' => '🇫🇷', 'BE' => '🇧🇪', 'CA' => '🇨🇦', 'AU' => '🇦🇺',
                        'BR' => '🇧🇷', 'IN' => '🇮🇳', 'JP' => '🇯🇵', 'RU' => '🇷🇺',
                        'CN' => '🇨🇳', 'KR' => '🇰🇷', 'IT' => '🇮🇹', 'ES' => '🇪🇸',
                        'SE' => '🇸🇪', 'NO' => '🇳🇴', 'DK' => '🇩🇰', 'FI' => '🇫🇮',
                        'PL' => '🇵🇱', 'UA' => '🇺🇦', 'TR' => '🇹🇷', 'SG' => '🇸🇬',
                    ];
                    ?>
                    <?php foreach ($topCountries as $code => $count): ?>
                    <div class="country-bar">
                        <span class="flag"><?= esc($flags[$code] ?? '🌍') ?></span>
                        <span class="name"><?= esc($countryNames[$code] ?? $code) ?></span>
                        <span class="bar-wrap">
                            <div class="cf-progress">
                                <div class="progress-bar bg-purple" style="width: <?= round(($count / $maxCountry) * 100, 1) ?>%;
                                    background: linear-gradient(90deg, #7c3aed, #a78bfa) !important;">
                                </div>
                            </div>
                        </span>
                        <span class="count"><?= number_format($count) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-browser-chrome me-1"></i> Top Browsers / User Agents
            </div>
            <div class="card-body" style="max-height: 380px; overflow-y: auto;">
                <?php if (empty($topBrowsers)): ?>
                    <p class="text-muted text-center py-3">Geen browser data beschikbaar.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th>Browser / Client</th><th class="text-end">Requests</th><th class="text-end">%</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalBrowser = array_sum($topBrowsers) ?: 1;
                            $i = 0;
                            ?>
                            <?php foreach ($topBrowsers as $browser => $count): ?>
                            <tr>
                                <td>
                                    <?php
                                    $icon = 'bi bi-browser-edge';
                                    if (stripos($browser, 'chrome') !== false) $icon = 'bi bi-browser-chrome';
                                    elseif (stripos($browser, 'firefox') !== false) $icon = 'bi bi-browser-firefox';
                                    elseif (stripos($browser, 'safari') !== false) $icon = 'bi bi-browser-safari';
                                    elseif (stripos($browser, 'edge') !== false) $icon = 'bi bi-browser-edge';
                                    elseif (stripos($browser, 'bot') !== false || stripos($browser, 'crawl') !== false) $icon = 'bi bi-robot';
                                    ?>
                                    <i class="<?= $icon ?> me-2" style="color: #a78bfa;"></i>
                                    <small><?= esc($browser) ?></small>
                                </td>
                                <td class="text-end"><small><?= number_format($count) ?></small></td>
                                <td class="text-end"><small><?= round(($count / $totalBrowser) * 100, 1) ?>%</small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== SUBDOMAINS TABLE ===== -->
<?php if (! empty($subdomainTotals)): ?>
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-diagram-3 me-1"></i> Subdomain / Zone Traffic Breakdown
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Zone / Subdomain</th>
                    <th class="text-end">Total Requests</th>
                    <th class="text-end">Share</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalSub = array_sum($subdomainTotals) ?: 1;
                ?>
                <?php foreach ($subdomainTotals as $sub => $count): ?>
                <tr>
                    <td><strong><?= esc($sub) ?></strong></td>
                    <td class="text-end"><?= number_format($count) ?></td>
                    <td class="text-end">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <div class="cf-progress" style="width: 100px;">
                                <div class="progress-bar" style="width: <?= round(($count / $totalSub) * 100, 1) ?>%;
                                    background: linear-gradient(90deg, #7c3aed, #a78bfa) !important;">
                                </div>
                            </div>
                            <small><?= round(($count / $totalSub) * 100, 1) ?>%</small>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ===== INFO BOXES ===== -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="info-box">
            <h6><i class="bi bi-shield-lock me-1"></i> DDoS Protection</h6>
            <p>Cloudflare's HTTP DDoS Managed Ruleset automatically detects and mitigates Layer 7 attacks. Your API key has <strong>Read</strong> access to monitor DDoS events in real time.</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="info-box">
            <h6><i class="bi bi-robot me-1"></i> Bot Management</h6>
            <p>Automated bot traffic is analyzed and classified. Legitimate bots (search engines) are allowed while malicious bots and scrapers are blocked. Bot analytics are available via the API.</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="info-box">
            <h6><i class="bi bi-file-earmark-lock me-1"></i> Page Shield</h6>
            <p>Monitors JavaScript dependencies and cookies on your pages to detect malicious script injections, Magecart-style attacks, and supply chain compromises.</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="info-box">
            <h6><i class="bi bi-fire me-1"></i> Firewall & WAF</h6>
            <p>Cloudflare's Web Application Firewall blocks SQL injection, XSS, and other OWASP Top 10 attacks. The Firewall Services API provides read access to event logs.</p>
        </div>
    </div>
</div>

<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const isDark = true;
    const gridColor = 'rgba(148, 163, 184, 0.08)';
    const textColor = '#94a3b8';

    // ── Chart defaults ────────────────────────────────────────────────
    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;

    // ── Traffic Chart (Pageviews + Visitors) ──────────────────────────
    <?php if (! empty($chartLabels)): ?>
    new Chart(document.getElementById('trafficChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Pageviews',
                data: <?= json_encode($chartPageViews) ?>,
                borderColor: '#a78bfa',
                backgroundColor: 'rgba(167, 139, 250, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
            }, {
                label: 'Unique Visitors',
                data: <?= json_encode($chartVisitors) ?>,
                borderColor: '#60a5fa',
                backgroundColor: 'rgba(96, 165, 250, 0.05)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { usePointStyle: true, padding: 20 } } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor } },
                x: { grid: { display: false } }
            }
        }
    });

    // ── Requests + Threats Chart ──────────────────────────────────────
    new Chart(document.getElementById('requestsThreatsChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Total Requests',
                data: <?= json_encode($chartRequests) ?>,
                backgroundColor: 'rgba(96, 165, 250, 0.5)',
                borderColor: '#60a5fa',
                borderWidth: 1,
            }, {
                label: 'Threats Blocked',
                data: <?= json_encode($chartThreats) ?>,
                backgroundColor: 'rgba(248, 113, 113, 0.6)',
                borderColor: '#f87171',
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { usePointStyle: true, padding: 20 } } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor } },
                x: { grid: { display: false } }
            }
        }
    });

    // ── Bandwidth Chart ───────────────────────────────────────────────
    new Chart(document.getElementById('bandwidthChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Bandwidth (MB)',
                data: <?= json_encode($chartBandwidth) ?>,
                backgroundColor: 'rgba(45, 212, 191, 0.4)',
                borderColor: '#2dd4bf',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor } },
                x: { grid: { display: false } }
            }
        }
    });

    // ── Bot Traffic Doughnut ──────────────────────────────────────────
    const botTotal = <?= (int) ($totals['bot_requests'] ?? 0) ?>;
    const humanTotal = <?= max(0, (int) ($totals['total_requests'] ?? 0) - (int) ($totals['bot_requests'] ?? 0)) ?>;
    new Chart(document.getElementById('botChart'), {
        type: 'doughnut',
        data: {
            labels: ['Human Traffic', 'Bot Traffic'],
            datasets: [{
                data: [humanTotal, botTotal],
                backgroundColor: ['rgba(167, 139, 250, 0.7)', 'rgba(248, 113, 113, 0.6)'],
                borderColor: ['#a78bfa', '#f87171'],
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
            }
        }
    });
    <?php endif; ?>

});
</script>
<?= $this->endSection() ?>