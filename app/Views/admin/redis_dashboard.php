<?= $this->extend('admin/layout') ?>

<?= $this->section('head') ?>
<style>
    .redis-hero {
        position: relative;
        overflow: hidden;
        padding: 1.5rem;
        border-radius: 20px;
        background:
            radial-gradient(circle at top left, rgba(16, 185, 129, 0.22), transparent 40%),
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.24), transparent 36%),
            linear-gradient(135deg, #0f172a, #111827 55%, #0b1120);
        border: 1px solid rgba(148, 163, 184, 0.16);
        margin-bottom: 1.5rem;
    }
    .redis-hero::after {
        content: '';
        position: absolute;
        inset: auto -20% -50% auto;
        width: 360px;
        height: 360px;
        border-radius: 50%;
        background: rgba(52, 211, 153, 0.08);
        filter: blur(20px);
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .45rem .8rem;
        border-radius: 999px;
        font-size: .8rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.45);
    }
    .status-dot {
        width: .65rem;
        height: .65rem;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.03);
    }
    .status-live .status-dot { background: #22c55e; }
    .status-disconnected .status-dot { background: #ef4444; }
    .redis-tabs .nav-link {
        color: #94a3b8;
        border: 1px solid transparent;
        border-radius: 999px;
        padding: .7rem 1rem;
        background: transparent;
    }
    .redis-tabs .nav-link.active {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.25), rgba(59, 130, 246, 0.22));
        border-color: rgba(52, 211, 153, 0.3);
        color: #f8fafc;
    }
    .metric-card {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.96), rgba(17, 24, 39, 0.84));
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 16px;
        padding: 1rem;
        min-height: 100%;
    }
    .metric-label {
        color: #94a3b8;
        font-size: .8rem;
        text-transform: uppercase;
        letter-spacing: .08em;
    }
    .metric-value {
        font-size: 1.85rem;
        font-weight: 800;
        color: #f8fafc;
    }
    .metric-subtle {
        color: #cbd5e1;
        font-size: .88rem;
    }
    .chart-card {
        min-height: 320px;
    }
    .panel-soft {
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 14px;
        padding: 1rem;
    }
    .alert-stack .alert:last-child {
        margin-bottom: 0;
    }
    .table-action {
        white-space: nowrap;
    }
    .key-cell {
        max-width: 380px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .console-output {
        min-height: 180px;
        max-height: 260px;
        overflow: auto;
        white-space: pre-wrap;
        font-family: Consolas, 'Courier New', monospace;
        font-size: .85rem;
        background: #050816;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.12);
        padding: 1rem;
        color: #e2e8f0;
    }
    @media (max-width: 991px) {
        .redis-tabs .nav-link {
            width: 100%;
            justify-content: flex-start;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="redis-hero">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
        <div>
            <div class="text-uppercase text-muted small mb-2" style="letter-spacing:.12em;">Redis Admin Dashboard</div>
            <h2 class="mb-2">Real-time Redis telemetry for Play2TV</h2>
            <p class="mb-0 text-muted">Live WebSocket updates for cache health, IPTV activity, slow queries, and guarded admin actions.</p>
        </div>
        <div class="d-flex flex-column align-items-lg-end gap-2">
            <span id="connection-status" class="status-pill status-disconnected">
                <span class="status-dot"></span>
                <span id="connection-status-text">Disconnected</span>
            </span>
            <div class="small text-muted">Last update: <span id="last-update">Never</span></div>
        </div>
    </div>
</section>

<?php if (! empty($dashboardError)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>Initial Redis snapshot failed: <?= esc($dashboardError) ?>
    </div>
<?php endif; ?>

<?php if (! empty($websocketError)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-broadcast-pin me-2"></i>WebSocket handshake is not ready: <?= esc($websocketError) ?>
    </div>
<?php endif; ?>

<div id="action-feedback"></div>
<div id="redis-alerts" class="alert-stack mb-4"></div>

<ul class="nav nav-pills redis-tabs flex-wrap gap-2 mb-4" id="redis-tabs" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button">Overview</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-performance" type="button">Performance</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-memory" type="button">Memory</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-keys" type="button">Keys</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-slowlog" type="button">Slowlog</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-iptv" type="button">IPTV</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-admin" type="button">Admin</button></li>
</ul>

<div class="tab-content">
    <section class="tab-pane fade show active" id="tab-overview">
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Uptime</div><div class="metric-value" id="overview-uptime">0s</div><div class="metric-subtle">Redis version <span id="overview-version">-</span></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Clients</div><div class="metric-value" id="overview-clients">0</div><div class="metric-subtle">Mode <span id="overview-mode">-</span></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Memory</div><div class="metric-value" id="overview-memory">0 B</div><div class="metric-subtle">Max <span id="overview-maxmemory">Not set</span></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Evictions</div><div class="metric-value" id="overview-evictions">0</div><div class="metric-subtle">Expired keys <span id="overview-expired">0</span></div></div></div>
        </div>
        <div class="row g-3">
            <div class="col-xl-4"><div class="card chart-card"><div class="card-header py-3"><strong>Memory Usage</strong></div><div class="card-body"><canvas id="memory-chart"></canvas></div></div></div>
            <div class="col-xl-4"><div class="card chart-card"><div class="card-header py-3"><strong>Commands / sec</strong></div><div class="card-body"><canvas id="commands-chart"></canvas></div></div></div>
            <div class="col-xl-4"><div class="card chart-card"><div class="card-header py-3"><strong>Clients</strong></div><div class="card-body"><canvas id="clients-chart"></canvas></div></div></div>
        </div>
    </section>

    <section class="tab-pane fade" id="tab-performance">
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Commands/sec</div><div class="metric-value" id="perf-ops">0</div><div class="metric-subtle">Total commands <span id="perf-total-commands">0</span></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Hit Rate</div><div class="metric-value" id="perf-hit-rate">0%</div><div class="metric-subtle">Hits <span id="perf-hits">0</span> / Misses <span id="perf-misses">0</span></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Input</div><div class="metric-value" id="perf-input">0 KB/s</div><div class="metric-subtle">Output <span id="perf-output">0 KB/s</span></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Connections</div><div class="metric-value" id="perf-total-connections">0</div><div class="metric-subtle">Rejected <span id="perf-rejected">0</span></div></div></div>
        </div>
        <div class="row g-3">
            <div class="col-lg-5"><div class="card"><div class="card-header py-3"><strong>Hit / Miss Ratio</strong></div><div class="card-body"><canvas id="hit-miss-chart"></canvas></div></div></div>
            <div class="col-lg-7"><div class="card"><div class="card-header py-3"><strong>Network and latency</strong></div><div class="card-body p-0"><table class="table mb-0"><tbody>
                <tr><td>Input throughput</td><td class="text-end" id="perf-table-input">0 KB/s</td></tr>
                <tr><td>Output throughput</td><td class="text-end" id="perf-table-output">0 KB/s</td></tr>
                <tr><td>Latest fork latency</td><td class="text-end" id="perf-fork">0 us</td></tr>
                <tr><td>Commands processed</td><td class="text-end" id="perf-table-total-commands">0</td></tr>
                <tr><td>Connections received</td><td class="text-end" id="perf-table-connections">0</td></tr>
            </tbody></table></div></div></div>
        </div>
    </section>

    <section class="tab-pane fade" id="tab-memory">
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Used Memory</div><div class="metric-value" id="memory-used">0 B</div><div class="metric-subtle">RSS <span id="memory-rss">0 B</span></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Peak</div><div class="metric-value" id="memory-peak">0 B</div><div class="metric-subtle">Allocator <span id="memory-allocator">-</span></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Fragmentation</div><div class="metric-value" id="memory-fragmentation">0</div><div class="metric-subtle">Policy <span id="memory-policy">-</span></div></div></div>
            <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Keys Without TTL</div><div class="metric-value" id="memory-no-ttl">0</div><div class="metric-subtle">Sampled <span id="memory-sampled">0</span></div></div></div>
        </div>
        <div class="row g-3">
            <div class="col-lg-6"><div class="panel-soft"><h5>TTL Sampling</h5><table class="table mb-0"><tbody>
                <tr><td>With TTL</td><td class="text-end" id="ttl-with">0</td></tr>
                <tr><td>Without TTL</td><td class="text-end" id="ttl-without">0</td></tr>
                <tr><td>Expired during sample</td><td class="text-end" id="ttl-expired">0</td></tr>
                <tr><td>Warning</td><td class="text-end" id="ttl-warning">No</td></tr>
            </tbody></table></div></div>
            <div class="col-lg-6"><div class="panel-soft"><h5>Keyspace Summary</h5><div class="small text-muted mb-3">Sampled prefixes from SCAN. Large databases are intentionally capped.</div><div id="prefix-summary" class="small"></div></div></div>
        </div>
    </section>

    <section class="tab-pane fade" id="tab-keys">
        <div class="card mb-4">
            <div class="card-header py-3"><strong>Search keys by prefix</strong></div>
            <div class="card-body">
                <form id="key-search-form" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label" for="key-pattern">Prefix or pattern</label>
                        <input id="key-pattern" name="pattern" class="form-control" placeholder="play2tv:session:*" value="play2tv:*">
                    </div>
                    <div class="col-md-4 d-grid">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-2"></i>Search keys</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <strong>Search results</strong>
                <span class="small text-muted">TTL and memory usage are sampled per result.</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Key</th><th>Type</th><th>TTL</th><th>Memory</th><th class="text-end">Action</th></tr></thead>
                        <tbody id="keys-table-body"><tr><td colspan="5" class="text-center text-muted py-4">Run a search to inspect matching keys.</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="tab-pane fade" id="tab-slowlog">
        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <strong>Slow queries</strong>
                <span class="small text-muted">Updated from live Redis telemetry.</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>ID</th><th>When</th><th>Duration</th><th>Client</th><th>Command</th></tr></thead>
                        <tbody id="slowlog-table-body"><tr><td colspan="5" class="text-center text-muted py-4">No slowlog entries yet.</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="tab-pane fade" id="tab-iptv">
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="metric-card"><div class="metric-label">Active Users</div><div class="metric-value" id="iptv-users">0</div><div class="metric-subtle">Derived from session-like prefixes</div></div></div>
            <div class="col-md-4"><div class="metric-card"><div class="metric-label">Active Streams</div><div class="metric-value" id="iptv-streams">0</div><div class="metric-subtle">Derived from stream activity keys</div></div></div>
            <div class="col-md-4"><div class="metric-card"><div class="metric-label">Cache Hits</div><div class="metric-value"><span id="iptv-epg-hits">0</span> / <span id="iptv-vod-hits">0</span></div><div class="metric-subtle">EPG / VOD counters</div></div></div>
        </div>
        <div class="panel-soft">
            <h5 class="mb-3">Redis-backed IPTV signals</h5>
            <p class="text-muted mb-0">These counters are prefix-driven and intentionally lightweight. Tune the IPTV prefixes in your environment if your production key naming differs.</p>
        </div>
    </section>

    <section class="tab-pane fade" id="tab-admin">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header py-3"><strong>Flush cache by prefix</strong></div>
                    <div class="card-body">
                        <form id="flush-prefix-form" class="d-grid gap-3">
                            <div>
                                <label class="form-label" for="flush-prefix">Allowed prefix</label>
                                <select id="flush-prefix" name="prefix" class="form-select">
                                    <?php foreach ($flushablePrefixes as $prefix): ?>
                                        <option value="<?= esc($prefix) ?>"><?= esc($prefix) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-trash me-2"></i>Flush selected prefix</button>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header py-3"><strong>Safe command console</strong></div>
                    <div class="card-body">
                        <form id="command-form" class="d-grid gap-3">
                            <div>
                                <label class="form-label" for="redis-command">Allowed commands</label>
                                <input id="redis-command" name="command" class="form-control" placeholder="INFO">
                                <div class="form-text">Allowed: <?= esc(implode(', ', $allowedCommands)) ?></div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-terminal me-2"></i>Execute</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header py-3"><strong>Command output</strong></div>
                    <div class="card-body">
                        <div id="command-output" class="console-output">No command executed yet.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const redisDashboardConfig = <?= json_encode([
    'initialSnapshot' => json_decode($initialSnapshotJson, true, 512, JSON_THROW_ON_ERROR),
    'initialDataUrl' => $initialDataUrl,
    'searchKeysUrl' => $searchKeysUrl,
    'deleteKeyUrl' => $deleteKeyUrl,
    'flushPrefixUrl' => $flushPrefixUrl,
    'executeCommandUrl' => $executeCommandUrl,
    'websocketUrl' => $websocketUrl,
    'websocketToken' => $websocketToken,
    'csrfName' => $csrfTokenName,
    'csrfHash' => $csrfHash,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>;

const state = {
    snapshot: redisDashboardConfig.initialSnapshot,
    csrf: {
        name: redisDashboardConfig.csrfName,
        hash: redisDashboardConfig.csrfHash,
    },
    socket: null,
    reconnectDelay: 1500,
    reconnectTimer: null,
    charts: {},
    history: {
        labels: [],
        memory: [],
        commands: [],
        clients: [],
        hits: [],
        misses: [],
    },
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#cbd5e1' } },
        tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#f8fafc',
            bodyColor: '#e2e8f0',
            borderColor: 'rgba(52, 211, 153, 0.25)',
            borderWidth: 1,
        },
    },
    scales: {
        x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.08)' } },
        y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.08)' }, beginAtZero: true },
    },
};

function initCharts() {
    state.charts.memory = new Chart(document.getElementById('memory-chart'), {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'Memory (MB)', data: [], borderColor: '#34d399', backgroundColor: 'rgba(52, 211, 153, 0.12)', fill: true, tension: 0.35 }] },
        options: chartOptions,
    });
    state.charts.commands = new Chart(document.getElementById('commands-chart'), {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'Ops/sec', data: [], borderColor: '#38bdf8', backgroundColor: 'rgba(56, 189, 248, 0.12)', fill: true, tension: 0.35 }] },
        options: chartOptions,
    });
    state.charts.clients = new Chart(document.getElementById('clients-chart'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Clients', data: [], backgroundColor: 'rgba(251, 191, 36, 0.6)', borderColor: '#fbbf24', borderWidth: 1, borderRadius: 6 }] },
        options: chartOptions,
    });
    state.charts.hitMiss = new Chart(document.getElementById('hit-miss-chart'), {
        type: 'doughnut',
        data: { labels: ['Hits', 'Misses'], datasets: [{ data: [0, 0], backgroundColor: ['#22c55e', '#ef4444'], borderWidth: 0 }] },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#cbd5e1' } },
            },
        },
    });
}

function formatNumber(value) {
    return new Intl.NumberFormat('en-US').format(Number(value || 0));
}

function formatBytes(value) {
    const bytes = Number(value || 0);
    if (! bytes) {
        return '0 B';
    }
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const power = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return `${(bytes / (1024 ** power)).toFixed(2)} ${units[power]}`;
}

function formatDuration(seconds) {
    const total = Number(seconds || 0);
    const days = Math.floor(total / 86400);
    const hours = Math.floor((total % 86400) / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    if (days > 0) {
        return `${days}d ${hours}h`;
    }
    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    return `${Math.max(total, 0)}s`;
}

function formatDate(value) {
    if (! value) {
        return 'Never';
    }
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString();
}

function setConnectionStatus(isLive, label) {
    const status = document.getElementById('connection-status');
    const text = document.getElementById('connection-status-text');
    status.classList.toggle('status-live', isLive);
    status.classList.toggle('status-disconnected', ! isLive);
    text.textContent = label;
}

function updateCsrf(payload) {
    if (! payload || ! payload.csrf) {
        return;
    }
    state.csrf.name = payload.csrf.name;
    state.csrf.hash = payload.csrf.hash;
}

function renderAlerts(alerts) {
    const container = document.getElementById('redis-alerts');
    if (! alerts || alerts.length === 0) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = alerts.map((alert) => {
        const level = alert.level === 'danger' ? 'danger' : 'warning';
        return `<div class="alert alert-${level}"><i class="bi bi-bell me-2"></i>${escapeHtml(alert.message)}</div>`;
    }).join('');
}

function renderPrefixSummary(prefixes) {
    const container = document.getElementById('prefix-summary');
    const entries = Object.entries(prefixes || {});
    if (entries.length === 0) {
        container.innerHTML = '<div class="text-muted">No sampled prefixes yet.</div>';
        return;
    }

    container.innerHTML = entries.map(([prefix, count]) => `
        <div class="d-flex justify-content-between border-bottom py-2" style="border-color:rgba(148,163,184,.08)!important;">
            <span>${escapeHtml(prefix)}</span>
            <strong>${formatNumber(count)}</strong>
        </div>
    `).join('');
}

function renderSlowlog(entries) {
    const body = document.getElementById('slowlog-table-body');
    if (! entries || entries.length === 0) {
        body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No slowlog entries yet.</td></tr>';
        return;
    }

    body.innerHTML = entries.map((entry) => `
        <tr>
            <td>${formatNumber(entry.id)}</td>
            <td>${escapeHtml(formatDate(entry.timestamp))}</td>
            <td>${formatNumber(entry.duration_microseconds)} us</td>
            <td>${escapeHtml(entry.client || '-')}</td>
            <td><code>${escapeHtml(entry.command || '')}</code></td>
        </tr>
    `).join('');
}

function renderKeys(results) {
    const body = document.getElementById('keys-table-body');
    const keys = results?.keys || [];
    if (keys.length === 0) {
        body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No keys matched this pattern.</td></tr>';
        return;
    }

    body.innerHTML = keys.map((item) => `
        <tr>
            <td class="key-cell" title="${escapeHtml(item.key)}">${escapeHtml(item.key)}</td>
            <td>${escapeHtml(item.type || 'unknown')}</td>
            <td>${escapeHtml(item.ttl_human || String(item.ttl ?? '-'))}</td>
            <td>${formatBytes(item.memory_usage)}</td>
            <td class="text-end table-action">
                <button class="btn btn-sm btn-outline-danger" data-delete-key="${escapeHtml(item.key)}">Delete</button>
            </td>
        </tr>
    `).join('');
}

function pushHistory(snapshot) {
    const label = new Date(snapshot.overview?.generated_at || Date.now()).toLocaleTimeString();
    state.history.labels.push(label);
    state.history.memory.push(Number(snapshot.memory?.used_memory || 0) / (1024 * 1024));
    state.history.commands.push(Number(snapshot.performance?.commands_per_sec || 0));
    state.history.clients.push(Number(snapshot.overview?.connected_clients || 0));
    state.history.hits = [Number(snapshot.performance?.hits || 0), Number(snapshot.performance?.misses || 0)];

    if (state.history.labels.length > 20) {
        state.history.labels.shift();
        state.history.memory.shift();
        state.history.commands.shift();
        state.history.clients.shift();
    }
}

function updateCharts() {
    state.charts.memory.data.labels = state.history.labels;
    state.charts.memory.data.datasets[0].data = state.history.memory;
    state.charts.memory.update('none');

    state.charts.commands.data.labels = state.history.labels;
    state.charts.commands.data.datasets[0].data = state.history.commands;
    state.charts.commands.update('none');

    state.charts.clients.data.labels = state.history.labels;
    state.charts.clients.data.datasets[0].data = state.history.clients;
    state.charts.clients.update('none');

    state.charts.hitMiss.data.datasets[0].data = state.history.hits;
    state.charts.hitMiss.update('none');
}

function applySnapshot(snapshot) {
    if (! snapshot) {
        return;
    }
    state.snapshot = snapshot;
    pushHistory(snapshot);
    updateCharts();

    const overview = snapshot.overview || {};
    const performance = snapshot.performance || {};
    const memory = snapshot.memory || {};
    const ttl = memory.ttl || {};
    const iptv = snapshot.iptv || {};

    document.getElementById('last-update').textContent = formatDate(overview.generated_at);
    document.getElementById('overview-uptime').textContent = formatDuration(overview.uptime_seconds);
    document.getElementById('overview-version').textContent = overview.redis_version || '-';
    document.getElementById('overview-clients').textContent = formatNumber(overview.connected_clients);
    document.getElementById('overview-mode').textContent = overview.mode || '-';
    document.getElementById('overview-memory').textContent = overview.used_memory_human || formatBytes(overview.used_memory);
    document.getElementById('overview-maxmemory').textContent = overview.maxmemory ? formatBytes(overview.maxmemory) : 'Not set';
    document.getElementById('overview-evictions').textContent = formatNumber(overview.evicted_keys);
    document.getElementById('overview-expired').textContent = formatNumber(overview.expired_keys);

    document.getElementById('perf-ops').textContent = formatNumber(performance.commands_per_sec);
    document.getElementById('perf-total-commands').textContent = formatNumber(performance.total_commands_processed);
    document.getElementById('perf-hit-rate').textContent = `${Number(performance.hit_rate || 0).toFixed(2)}%`;
    document.getElementById('perf-hits').textContent = formatNumber(performance.hits);
    document.getElementById('perf-misses').textContent = formatNumber(performance.misses);
    document.getElementById('perf-input').textContent = `${Number(performance.input_kbps || 0).toFixed(2)} KB/s`;
    document.getElementById('perf-output').textContent = `${Number(performance.output_kbps || 0).toFixed(2)} KB/s`;
    document.getElementById('perf-total-connections').textContent = formatNumber(performance.total_connections_received);
    document.getElementById('perf-rejected').textContent = formatNumber(performance.rejected_connections);
    document.getElementById('perf-table-input').textContent = `${Number(performance.input_kbps || 0).toFixed(2)} KB/s`;
    document.getElementById('perf-table-output').textContent = `${Number(performance.output_kbps || 0).toFixed(2)} KB/s`;
    document.getElementById('perf-fork').textContent = `${formatNumber(performance.latest_fork_usec)} us`;
    document.getElementById('perf-table-total-commands').textContent = formatNumber(performance.total_commands_processed);
    document.getElementById('perf-table-connections').textContent = formatNumber(performance.total_connections_received);

    document.getElementById('memory-used').textContent = memory.used_memory_human || formatBytes(memory.used_memory);
    document.getElementById('memory-rss').textContent = memory.used_memory_rss_human || formatBytes(memory.used_memory_rss);
    document.getElementById('memory-peak').textContent = memory.used_memory_peak_human || formatBytes(memory.used_memory_peak);
    document.getElementById('memory-allocator').textContent = memory.allocator || '-';
    document.getElementById('memory-fragmentation').textContent = Number(memory.mem_fragmentation_ratio || 0).toFixed(2);
    document.getElementById('memory-policy').textContent = memory.maxmemory_policy || '-';
    document.getElementById('memory-no-ttl').textContent = formatNumber(ttl.without_ttl);
    document.getElementById('memory-sampled').textContent = formatNumber(ttl.sampled);
    document.getElementById('ttl-with').textContent = formatNumber(ttl.with_ttl);
    document.getElementById('ttl-without').textContent = formatNumber(ttl.without_ttl);
    document.getElementById('ttl-expired').textContent = formatNumber(ttl.expired);
    document.getElementById('ttl-warning').textContent = ttl.without_ttl_warning ? 'Yes' : 'No';

    document.getElementById('iptv-users').textContent = formatNumber(iptv.active_users);
    document.getElementById('iptv-streams').textContent = formatNumber(iptv.active_streams);
    document.getElementById('iptv-epg-hits').textContent = formatNumber(iptv.cache_hits?.epg);
    document.getElementById('iptv-vod-hits').textContent = formatNumber(iptv.cache_hits?.vod);

    renderPrefixSummary(snapshot.keys?.sampled_prefixes || {});
    renderSlowlog(snapshot.slowlog || []);
    renderAlerts(snapshot.alerts || []);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showFeedback(message, level = 'success') {
    document.getElementById('action-feedback').innerHTML = `<div class="alert alert-${level}">${escapeHtml(message)}</div>`;
}

async function fetchJson(url, options = {}) {
    const response = await fetch(url, options);
    const payload = await response.json();
    updateCsrf(payload);

    if (! response.ok) {
        throw new Error(payload.error || 'Request failed.');
    }

    return payload;
}

function buildPostBody(data) {
    const params = new URLSearchParams();
    params.set(state.csrf.name, state.csrf.hash);
    Object.entries(data).forEach(([key, value]) => params.set(key, value));
    return params;
}

async function refreshInitialSnapshot() {
    try {
        const payload = await fetchJson(redisDashboardConfig.initialDataUrl);
        applySnapshot(payload.snapshot);
    } catch (error) {
        showFeedback(error.message, 'warning');
    }
}

function connectWebSocket() {
    if (! redisDashboardConfig.websocketUrl || ! redisDashboardConfig.websocketToken) {
        setConnectionStatus(false, 'Disconnected');
        return;
    }

    const separator = redisDashboardConfig.websocketUrl.includes('?') ? '&' : '?';
    const socketUrl = `${redisDashboardConfig.websocketUrl}${separator}token=${encodeURIComponent(redisDashboardConfig.websocketToken)}`;
    const socket = new WebSocket(socketUrl);
    state.socket = socket;

    socket.addEventListener('open', () => {
        setConnectionStatus(true, 'Live');
        state.reconnectDelay = 1500;
    });

    socket.addEventListener('message', (event) => {
        try {
            const payload = JSON.parse(event.data);
            if (payload.type === 'snapshot') {
                applySnapshot(payload.data);
            }
            if (payload.type === 'error') {
                showFeedback(payload.message || 'WebSocket update failed.', 'warning');
            }
        } catch (error) {
            console.error('Invalid WebSocket payload', error);
        }
    });

    socket.addEventListener('close', () => {
        setConnectionStatus(false, 'Disconnected');
        scheduleReconnect();
    });

    socket.addEventListener('error', () => {
        setConnectionStatus(false, 'Disconnected');
        socket.close();
    });
}

function scheduleReconnect() {
    clearTimeout(state.reconnectTimer);
    state.reconnectTimer = setTimeout(() => {
        connectWebSocket();
        state.reconnectDelay = Math.min(state.reconnectDelay * 1.5, 10000);
    }, state.reconnectDelay);
}

document.getElementById('key-search-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const pattern = document.getElementById('key-pattern').value || '*';
    try {
        const payload = await fetchJson(`${redisDashboardConfig.searchKeysUrl}?pattern=${encodeURIComponent(pattern)}`);
        renderKeys(payload.keys);
    } catch (error) {
        showFeedback(error.message, 'danger');
    }
});

document.getElementById('keys-table-body').addEventListener('click', async (event) => {
    const target = event.target.closest('[data-delete-key]');
    if (! target) {
        return;
    }

    const key = target.getAttribute('data-delete-key');
    if (! window.confirm(`Delete Redis key ${key}?`)) {
        return;
    }

    try {
        await fetchJson(redisDashboardConfig.deleteKeyUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: buildPostBody({ key }),
        });
        showFeedback(`Deleted key ${key}.`);
        document.getElementById('key-search-form').dispatchEvent(new Event('submit'));
        refreshInitialSnapshot();
    } catch (error) {
        showFeedback(error.message, 'danger');
    }
});

document.getElementById('flush-prefix-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const prefix = document.getElementById('flush-prefix').value;
    if (! window.confirm(`Flush all keys for prefix ${prefix}?`)) {
        return;
    }

    try {
        const payload = await fetchJson(redisDashboardConfig.flushPrefixUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: buildPostBody({ prefix }),
        });
        showFeedback(`Deleted ${payload.flush.deleted} keys for prefix ${prefix}.`);
        refreshInitialSnapshot();
    } catch (error) {
        showFeedback(error.message, 'danger');
    }
});

document.getElementById('command-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const command = document.getElementById('redis-command').value.trim();
    if (! command) {
        showFeedback('Enter a Redis command first.', 'warning');
        return;
    }

    try {
        const payload = await fetchJson(redisDashboardConfig.executeCommandUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: buildPostBody({ command }),
        });
        document.getElementById('command-output').textContent = JSON.stringify(payload.execution.result, null, 2);
        showFeedback(`Executed ${command}.`);
        refreshInitialSnapshot();
    } catch (error) {
        document.getElementById('command-output').textContent = error.message;
        showFeedback(error.message, 'danger');
    }
});

initCharts();
applySnapshot(state.snapshot);
refreshInitialSnapshot();
connectWebSocket();
</script>
<?= $this->endSection() ?>