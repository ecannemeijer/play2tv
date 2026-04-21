<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card h-100">
            <div class="stat-label">Totaal events</div>
            <div class="stat-value"><?= number_format((int) ($overview['total'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card h-100">
            <div class="stat-label">Laatste 24 uur</div>
            <div class="stat-value"><?= number_format((int) ($overview['last24h'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card h-100">
            <div class="stat-label">Crashes / errors 24 uur</div>
            <div class="stat-value"><?= number_format((int) ($overview['crashes24h'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card h-100">
            <div class="stat-label">Manual reports 24 uur</div>
            <div class="stat-value"><?= number_format((int) ($overview['manualReports24h'] ?? 0)) ?></div>
        </div>
    </div>
</div>

<p class="text-muted small mb-4">
    Laatst opgeslagen event:
    <strong><?= ! empty($overview['latestCreatedAt']) ? esc(date('d-m-Y H:i:s', strtotime((string) $overview['latestCreatedAt']))) : 'geen data' ?></strong>
</p>

<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h6>
    </div>
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Zoeken</label>
                <input type="text" name="q" class="form-control" value="<?= esc($query) ?>" placeholder="event, kanaal, actie, data">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Type</label>
                <input type="text" name="type" class="form-control" value="<?= esc($type) ?>" placeholder="player_error">
            </div>
            <?= $this->extend('admin/layout') ?>

            <?= $this->section('head') ?>
            <style>
                .telemetry-shell {
                    display: grid;
                    gap: 1.5rem;
                }
                .telemetry-hero {
                    position: relative;
                    overflow: hidden;
                    border-radius: 20px;
                    padding: 1.5rem;
                    background:
                        radial-gradient(circle at top right, rgba(34, 197, 94, .22), transparent 35%),
                        radial-gradient(circle at bottom left, rgba(59, 130, 246, .24), transparent 40%),
                        linear-gradient(135deg, #111827, #0f172a 60%, #111827);
                    border: 1px solid rgba(148, 163, 184, .16);
                    box-shadow: 0 24px 60px rgba(2, 6, 23, .35);
                }
                .telemetry-hero-grid {
                    display: grid;
                    grid-template-columns: minmax(0, 1.6fr) repeat(4, minmax(0, 1fr));
                    gap: 1rem;
                }
                .telemetry-hero-copy {
                    min-height: 100%;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    gap: 1rem;
                }
                .telemetry-kicker {
                    display: inline-flex;
                    align-items: center;
                    gap: .5rem;
                    padding: .35rem .7rem;
                    border-radius: 999px;
                    background: rgba(15, 23, 42, .55);
                    border: 1px solid rgba(148, 163, 184, .14);
                    color: #bfdbfe;
                    font-size: .78rem;
                    letter-spacing: .08em;
                    text-transform: uppercase;
                }
                .telemetry-hero h2 {
                    font-size: clamp(1.5rem, 3vw, 2.4rem);
                    line-height: 1.05;
                    margin: 0;
                    max-width: 14ch;
                }
                .telemetry-hero p {
                    margin: 0;
                    color: rgba(226, 232, 240, .74);
                    max-width: 54ch;
                }
                .telemetry-latest {
                    display: inline-flex;
                    flex-wrap: wrap;
                    gap: .5rem;
                    align-items: center;
                    color: rgba(226, 232, 240, .82);
                }
                .telemetry-stat-tile {
                    padding: 1.1rem 1rem;
                    border-radius: 18px;
                    background: rgba(15, 23, 42, .52);
                    border: 1px solid rgba(148, 163, 184, .16);
                    backdrop-filter: blur(12px);
                }
                .telemetry-stat-tile .label {
                    display: block;
                    color: #93c5fd;
                    font-size: .78rem;
                    text-transform: uppercase;
                    letter-spacing: .08em;
                    margin-bottom: .5rem;
                }
                .telemetry-stat-tile .value {
                    font-size: clamp(1.45rem, 2.2vw, 2rem);
                    font-weight: 800;
                    line-height: 1;
                    margin-bottom: .35rem;
                }
                .telemetry-stat-tile .meta {
                    color: rgba(203, 213, 225, .62);
                    font-size: .82rem;
                }
                .telemetry-layout {
                    display: grid;
                    grid-template-columns: minmax(320px, 390px) minmax(0, 1fr);
                    gap: 1.5rem;
                    align-items: start;
                }
                .telemetry-panel {
                    border-radius: 22px;
                    background: linear-gradient(180deg, rgba(15, 23, 42, .96), rgba(15, 23, 42, .88));
                    border: 1px solid rgba(148, 163, 184, .14);
                    box-shadow: 0 16px 42px rgba(2, 6, 23, .24);
                }
                .telemetry-panel-header {
                    padding: 1.15rem 1.25rem;
                    border-bottom: 1px solid rgba(148, 163, 184, .11);
                }
                .telemetry-panel-header h6 {
                    margin: 0;
                    font-size: 1rem;
                }
                .telemetry-panel-body {
                    padding: 1.25rem;
                }
                .fingerprint-scroll {
                    display: grid;
                    gap: .85rem;
                    max-height: 920px;
                    overflow: auto;
                    padding-right: .25rem;
                }
                .fingerprint-card {
                    display: grid;
                    gap: .75rem;
                    text-decoration: none;
                    color: inherit;
                    border-radius: 18px;
                    padding: 1rem;
                    background: rgba(15, 23, 42, .64);
                    border: 1px solid rgba(148, 163, 184, .12);
                    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
                }
                .fingerprint-card:hover {
                    transform: translateY(-1px);
                    border-color: rgba(96, 165, 250, .38);
                    box-shadow: 0 12px 30px rgba(15, 23, 42, .28);
                }
                .fingerprint-card.active {
                    background: linear-gradient(180deg, rgba(29, 78, 216, .24), rgba(15, 23, 42, .82));
                    border-color: rgba(96, 165, 250, .52);
                    box-shadow: 0 18px 36px rgba(30, 64, 175, .18);
                }
                .fingerprint-card-top,
                .fingerprint-card-bottom,
                .fingerprint-card-metrics {
                    display: flex;
                    justify-content: space-between;
                    gap: .75rem;
                    align-items: flex-start;
                }
                .fingerprint-hash {
                    display: inline-flex;
                    align-items: center;
                    gap: .55rem;
                    font-size: .95rem;
                    font-weight: 700;
                    letter-spacing: .01em;
                }
                .fingerprint-hash code,
                .telemetry-detail-head code,
                .event-pill code {
                    background: rgba(30, 41, 59, .9);
                    color: #dbeafe;
                }
                .fingerprint-hint,
                .telemetry-subtle {
                    color: rgba(203, 213, 225, .62);
                    font-size: .84rem;
                }
                .metric-pill,
                .type-pill,
                .event-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: .35rem;
                    padding: .35rem .7rem;
                    border-radius: 999px;
                    background: rgba(30, 41, 59, .7);
                    border: 1px solid rgba(148, 163, 184, .11);
                    color: #cbd5e1;
                    font-size: .78rem;
                    white-space: nowrap;
                }
                .metric-pill.error {
                    background: rgba(127, 29, 29, .55);
                    color: #fecaca;
                    border-color: rgba(248, 113, 113, .28);
                }
                .telemetry-detail-head {
                    display: grid;
                    gap: 1rem;
                    padding: 1.1rem 1.25rem 1.2rem;
                    border-bottom: 1px solid rgba(148, 163, 184, .11);
                    background: linear-gradient(180deg, rgba(15, 23, 42, .98), rgba(15, 23, 42, .9));
                    border-radius: 22px 22px 0 0;
                }
                .telemetry-detail-title {
                    display: flex;
                    justify-content: space-between;
                    gap: 1rem;
                    align-items: flex-start;
                    flex-wrap: wrap;
                }
                .telemetry-detail-title h3 {
                    margin: 0;
                    font-size: 1.25rem;
                }
                .telemetry-chip-row,
                .telemetry-action-row,
                .telemetry-type-cloud {
                    display: flex;
                    flex-wrap: wrap;
                    gap: .55rem;
                }
                .telemetry-chip {
                    display: inline-flex;
                    align-items: center;
                    gap: .4rem;
                    padding: .5rem .75rem;
                    border-radius: 14px;
                    background: rgba(30, 41, 59, .68);
                    border: 1px solid rgba(148, 163, 184, .11);
                    color: #dbeafe;
                    font-size: .84rem;
                }
                .telemetry-chip strong {
                    color: #fff;
                }
                .telemetry-detail-grid {
                    display: grid;
                    grid-template-columns: minmax(0, 1.15fr) minmax(320px, .85fr);
                    gap: 1.25rem;
                    padding: 1.25rem;
                }
                .telemetry-event-stream {
                    display: grid;
                    gap: .9rem;
                }
                .telemetry-event-card {
                    border-radius: 18px;
                    padding: 1rem;
                    background: rgba(15, 23, 42, .58);
                    border: 1px solid rgba(148, 163, 184, .12);
                    transition: border-color .18s ease, transform .18s ease;
                }
                .telemetry-event-card.active {
                    border-color: rgba(56, 189, 248, .42);
                    background: linear-gradient(180deg, rgba(8, 47, 73, .46), rgba(15, 23, 42, .78));
                }
                .telemetry-event-card:hover {
                    transform: translateY(-1px);
                    border-color: rgba(96, 165, 250, .35);
                }
                .telemetry-event-card a {
                    color: inherit;
                    text-decoration: none;
                    display: grid;
                    gap: .7rem;
                }
                .telemetry-event-top {
                    display: flex;
                    justify-content: space-between;
                    gap: .75rem;
                    align-items: center;
                    flex-wrap: wrap;
                }
                .telemetry-event-meta {
                    display: flex;
                    flex-wrap: wrap;
                    gap: .5rem;
                }
                .telemetry-event-payload {
                    min-height: 100%;
                    display: grid;
                    gap: 1rem;
                    align-content: start;
                }
                .telemetry-payload-box {
                    margin: 0;
                    padding: 1rem;
                    border-radius: 18px;
                    background: rgba(2, 6, 23, .86);
                    border: 1px solid rgba(30, 41, 59, .95);
                    color: #dbeafe;
                    white-space: pre-wrap;
                    word-break: break-word;
                    max-height: 680px;
                    overflow: auto;
                    font-size: .88rem;
                    line-height: 1.45;
                }
                .telemetry-empty {
                    padding: 2rem;
                    border-radius: 18px;
                    background: rgba(15, 23, 42, .48);
                    border: 1px dashed rgba(148, 163, 184, .18);
                    color: rgba(203, 213, 225, .68);
                    text-align: center;
                }
                @media (max-width: 1399px) {
                    .telemetry-hero-grid {
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                    }
                    .telemetry-hero-copy {
                        grid-column: 1 / -1;
                    }
                }
                @media (max-width: 1199px) {
                    .telemetry-layout,
                    .telemetry-detail-grid {
                        grid-template-columns: 1fr;
                    }
                }
                @media (max-width: 767px) {
                    .telemetry-hero-grid {
                        grid-template-columns: 1fr;
                    }
                    .telemetry-panel-header,
                    .telemetry-panel-body,
                    .telemetry-detail-head,
                    .telemetry-detail-grid {
                        padding-left: 1rem;
                        padding-right: 1rem;
                    }
                }
            </style>
            <?= $this->endSection() ?>

            <?= $this->section('content') ?>

            <?php
                $selectedFingerprintLabel = $selectedFingerprint === '' || $selectedFingerprint === 'unknown'
                    ? 'Onbekende fingerprint'
                    : $selectedFingerprint;
                $selectedFingerprintLinkQuery = $baseQuery;
                if ($selectedFingerprint !== '') {
                    $selectedFingerprintLinkQuery['fingerprint'] = $selectedFingerprint;
                }
                $selectedEventId = (int) ($selectedEvent['id'] ?? 0);
            ?>

            <div class="telemetry-shell">
                <section class="telemetry-hero">
                    <div class="telemetry-hero-grid">
                        <div class="telemetry-hero-copy">
                            <div class="d-grid gap-3">
                                <span class="telemetry-kicker"><i class="bi bi-fingerprint"></i> Telemetry fingerprints</span>
                                <div class="d-grid gap-2">
                                    <h2>Groepeer binnenkomende telemetry per device fingerprint.</h2>
                                    <p>Klik op een fingerprint om alle events, payloads en terugkerende problemen van dat device in een moderne master-detail weergave te bekijken.</p>
                                </div>
                            </div>
                            <div class="telemetry-latest">
                                <span class="telemetry-subtle">Laatst opgeslagen event</span>
                                <strong><?= ! empty($overview['latestCreatedAt']) ? esc(date('d-m-Y H:i:s', strtotime((string) ($overview['latestCreatedAt'])))) : 'geen data' ?></strong>
                            </div>
                        </div>
                        <div class="telemetry-stat-tile">
                            <span class="label">Events</span>
                            <div class="value"><?= number_format((int) ($overview['total'] ?? 0)) ?></div>
                            <div class="meta">Alle opgeslagen telemetry events</div>
                        </div>
                        <div class="telemetry-stat-tile">
                            <span class="label">Fingerprints</span>
                            <div class="value"><?= number_format((int) ($overview['uniqueFingerprints'] ?? 0)) ?></div>
                            <div class="meta">Unieke device groepen</div>
                        </div>
                        <div class="telemetry-stat-tile">
                            <span class="label">24 uur</span>
                            <div class="value"><?= number_format((int) ($overview['last24h'] ?? 0)) ?></div>
                            <div class="meta">Nieuwe events in de laatste 24 uur</div>
                        </div>
                        <div class="telemetry-stat-tile">
                            <span class="label">Errors</span>
                            <div class="value"><?= number_format((int) ($overview['crashes24h'] ?? 0)) ?></div>
                            <div class="meta">Crashes en errors in 24 uur</div>
                        </div>
                    </div>
                </section>

                <div class="telemetry-panel">
                    <div class="telemetry-panel-header">
                        <h6><i class="bi bi-funnel me-2"></i>Filters en acties</h6>
                    </div>
                    <div class="telemetry-panel-body">
                        <form method="get" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Zoeken</label>
                                <input type="text" name="q" class="form-control" value="<?= esc($query) ?>" placeholder="event, kanaal, actie, data">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Type</label>
                                <input type="text" name="type" class="form-control" value="<?= esc($type) ?>" placeholder="player_error">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Severity</label>
                                <select name="severity" class="form-select">
                                    <option value="">Alle</option>
                                    <?php foreach (['info', 'warning', 'error'] as $option): ?>
                                        <option value="<?= esc($option) ?>" <?= $severity === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">App versie</label>
                                <input type="text" name="app_version" class="form-control" value="<?= esc($appVersion) ?>" placeholder="1.0.0">
                            </div>
                            <div class="col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-1"></i>Filter
                                </button>
                                <a href="<?= base_url('admin/telemetry') ?>" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>

                        <div class="telemetry-action-row mt-3 pt-3 border-top">
                            <a href="<?= base_url('admin/telemetry/export/csv') . ($selectedFingerprintLinkQuery !== [] ? '?' . http_build_query($selectedFingerprintLinkQuery) : '') ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-download me-1"></i>Export CSV
                            </a>
                            <a href="<?= base_url('admin/telemetry/export/json') . ($selectedFingerprintLinkQuery !== [] ? '?' . http_build_query($selectedFingerprintLinkQuery) : '') ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-filetype-json me-1"></i>Export JSON
                            </a>
                            <form method="post" action="<?= base_url('admin/telemetry/delete-filtered') ?>" onsubmit="return confirm('Weet je zeker dat je alle gefilterde telemetry events wilt verwijderen?');" class="d-inline-flex gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="q" value="<?= esc($query) ?>">
                                <input type="hidden" name="type" value="<?= esc($type) ?>">
                                <input type="hidden" name="severity" value="<?= esc($severity) ?>">
                                <input type="hidden" name="app_version" value="<?= esc($appVersion) ?>">
                                <input type="hidden" name="fingerprint" value="<?= esc($selectedFingerprint) ?>">
                                <button type="submit" class="btn btn-outline-warning btn-sm" <?= ($baseQuery === [] && $selectedFingerprint === '') ? 'disabled' : '' ?>>
                                    <i class="bi bi-funnel-fill me-1"></i>Verwijder huidige selectie
                                </button>
                            </form>
                            <form method="post" action="<?= base_url('admin/telemetry/prune') ?>" onsubmit="return confirm('Verwijder alle telemetry ouder dan 30 dagen?');" class="d-inline-flex gap-2 align-items-center">
                                <?= csrf_field() ?>
                                <input type="hidden" name="days" value="30">
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-clock-history me-1"></i>Opschonen 30+ dagen
                                </button>
                            </form>
                            <form method="post" action="<?= base_url('admin/telemetry/delete-all') ?>" onsubmit="return confirm('Weet je zeker dat je ALLE telemetry events wilt verwijderen?');" class="d-inline-flex gap-2">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash3 me-1"></i>Verwijder alles
                                </button>
                            </form>
                        </div>

                        <div class="telemetry-action-row mt-3">
                            <a href="<?= base_url('admin/telemetry?severity=error') ?>" class="btn btn-sm btn-outline-danger">Alle errors</a>
                            <a href="<?= base_url('admin/telemetry?type=manual_report') ?>" class="btn btn-sm btn-outline-secondary">Manual reports</a>
                            <a href="<?= base_url('admin/telemetry?type=player_rebuffer') ?>" class="btn btn-sm btn-outline-secondary">Rebuffers</a>
                            <a href="<?= base_url('admin/telemetry?type=crash') ?>" class="btn btn-sm btn-outline-secondary">Crashes</a>
                        </div>
                    </div>
                </div>

                <div class="telemetry-layout">
                    <div class="telemetry-panel">
                        <div class="telemetry-panel-header d-flex justify-content-between align-items-center">
                            <h6><i class="bi bi-person-bounding-box me-2"></i>Fingerprint groepen</h6>
                            <span class="badge bg-secondary"><?= number_format($totalFingerprints) ?> groepen</span>
                        </div>
                        <div class="telemetry-panel-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="telemetry-subtle"><?= number_format($totalEvents) ?> events binnen huidige filters</span>
                                <span class="telemetry-subtle">Klik op een fingerprint voor detail</span>
                            </div>
                            <?php if (empty($fingerprintGroups)): ?>
                                <div class="telemetry-empty">Geen fingerprint-groepen gevonden voor de huidige filters.</div>
                            <?php else: ?>
                                <div class="fingerprint-scroll">
                                    <?php foreach ($fingerprintGroups as $group): ?>
                                        <?php
                                            $groupFingerprint = (string) ($group['fingerprint_key'] ?? 'unknown');
                                            $groupLabel = $groupFingerprint === 'unknown' ? 'Onbekende fingerprint' : $groupFingerprint;
                                            $groupQuery = array_merge($baseQuery, ['page' => $page, 'fingerprint' => $groupFingerprint]);
                                            $groupTypes = array_filter(array_map('trim', explode(',', (string) ($group['event_types_csv'] ?? ''))));
                                        ?>
                                        <a href="<?= current_url() . '?' . http_build_query($groupQuery) ?>" class="fingerprint-card <?= $selectedFingerprint === $groupFingerprint ? 'active' : '' ?>">
                                            <div class="fingerprint-card-top">
                                                <div class="d-grid gap-1">
                                                    <div class="fingerprint-hash">
                                                        <i class="bi bi-fingerprint"></i>
                                                        <code><?= esc($groupLabel === 'Onbekende fingerprint' ? $groupLabel : substr($groupLabel, 0, 18) . '…') ?></code>
                                                    </div>
                                                    <span class="fingerprint-hint"><?= esc((string) ($group['sample_device_name'] ?: 'Onbekend device')) ?><?= ! empty($group['sample_app_version']) ? ' · v' . esc((string) $group['sample_app_version']) : '' ?></span>
                                                </div>
                                                <?php if ((int) ($group['error_events'] ?? 0) > 0): ?>
                                                    <span class="metric-pill error"><i class="bi bi-exclamation-octagon"></i><?= number_format((int) $group['error_events']) ?> errors</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="fingerprint-card-metrics">
                                                <span class="metric-pill"><i class="bi bi-activity"></i><?= number_format((int) ($group['total_events'] ?? 0)) ?> events</span>
                                                <span class="metric-pill"><i class="bi bi-diagram-3"></i><?= number_format((int) ($group['unique_event_types'] ?? 0)) ?> types</span>
                                                <span class="metric-pill"><i class="bi bi-broadcast"></i><?= number_format((int) ($group['unique_channels'] ?? 0)) ?> kanalen</span>
                                            </div>
                                            <div class="fingerprint-card-bottom">
                                                <div class="telemetry-subtle">Laatst gezien <?= ! empty($group['latest_created_at']) ? esc(date('d-m-Y H:i:s', strtotime((string) ($group['latest_created_at'])))) : '—' ?></div>
                                                <div class="d-flex flex-wrap justify-content-end gap-2">
                                                    <?php foreach (array_slice($groupTypes, 0, 3) as $eventType): ?>
                                                        <span class="type-pill"><?= esc($eventType) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($totalPages > 1): ?>
                            <div class="telemetry-panel-body pt-0">
                                <nav aria-label="Fingerprint pagination">
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php
                                            $previousQuery = http_build_query(array_merge($baseQuery, ['page' => max(1, $page - 1)]));
                                            $nextQuery = http_build_query(array_merge($baseQuery, ['page' => min($totalPages, $page + 1)]));
                                        ?>
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $page <= 1 ? '#' : current_url() . '?' . $previousQuery ?>">Vorige</a>
                                        </li>
                                        <?php for ($currentPage = max(1, $page - 2); $currentPage <= min($totalPages, $page + 2); $currentPage++): ?>
                                            <li class="page-item <?= $currentPage === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= current_url() . '?' . http_build_query(array_merge($baseQuery, ['page' => $currentPage])) ?>">
                                                    <?= $currentPage ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $page >= $totalPages ? '#' : current_url() . '?' . $nextQuery ?>">Volgende</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="telemetry-panel">
                        <div class="telemetry-detail-head">
                            <?php if (empty($selectedFingerprintSummary)): ?>
                                <div class="telemetry-empty mb-0">Selecteer een fingerprint om alle gegroepeerde telemetry data te bekijken.</div>
                            <?php else: ?>
                                <?php $selectedTypes = array_filter(array_map('trim', explode(',', (string) ($selectedFingerprintSummary['event_types_csv'] ?? '')))); ?>
                                <div class="telemetry-detail-title">
                                    <div class="d-grid gap-2">
                                        <span class="telemetry-subtle">Device fingerprint</span>
                                        <h3><code><?= esc($selectedFingerprintLabel) ?></code></h3>
                                        <span class="telemetry-subtle">Klik in de eventlijst hieronder om de volledige telemetry payload te bekijken.</span>
                                    </div>
                                    <div class="telemetry-chip-row">
                                        <span class="telemetry-chip"><strong><?= number_format((int) ($selectedFingerprintSummary['total_events'] ?? 0)) ?></strong> events</span>
                                        <span class="telemetry-chip"><strong><?= number_format((int) ($selectedFingerprintSummary['error_events'] ?? 0)) ?></strong> errors</span>
                                        <span class="telemetry-chip"><strong><?= number_format((int) ($selectedFingerprintSummary['warning_events'] ?? 0)) ?></strong> warnings</span>
                                        <span class="telemetry-chip"><strong><?= number_format((int) ($selectedFingerprintSummary['unique_event_types'] ?? 0)) ?></strong> types</span>
                                    </div>
                                </div>
                                <div class="telemetry-chip-row">
                                    <span class="telemetry-chip"><i class="bi bi-phone"></i><strong><?= esc((string) ($selectedFingerprintSummary['sample_device_name'] ?: 'Onbekend device')) ?></strong></span>
                                    <span class="telemetry-chip"><i class="bi bi-box"></i><strong><?= esc((string) ($selectedFingerprintSummary['sample_app_version'] ?: 'Onbekende versie')) ?></strong></span>
                                    <span class="telemetry-chip"><i class="bi bi-calendar-event"></i><strong><?= ! empty($selectedFingerprintSummary['latest_created_at']) ? esc(date('d-m-Y H:i:s', strtotime((string) ($selectedFingerprintSummary['latest_created_at'])))) : '—' ?></strong></span>
                                </div>
                                <div class="telemetry-type-cloud">
                                    <?php foreach (array_slice($selectedTypes, 0, 8) as $eventType): ?>
                                        <span class="type-pill"><code><?= esc($eventType) ?></code></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="telemetry-detail-grid">
                            <div class="telemetry-event-stream">
                                <?php if (empty($selectedFingerprintEvents)): ?>
                                    <div class="telemetry-empty">Geen events binnen deze fingerprintgroep.</div>
                                <?php else: ?>
                                    <?php foreach ($selectedFingerprintEvents as $event): ?>
                                        <?php
                                            $eventQuery = array_merge($selectedFingerprintLinkQuery, [
                                                'page' => $page,
                                                'id' => $event['id'],
                                            ]);
                                            $severityClass = match ($event['severity'] ?? '') {
                                                'error' => 'bg-danger',
                                                'warning' => 'bg-warning text-dark',
                                                default => 'bg-secondary',
                                            };
                                        ?>
                                        <div class="telemetry-event-card <?= $selectedEventId === (int) ($event['id'] ?? 0) ? 'active' : '' ?>">
                                            <a href="<?= current_url() . '?' . http_build_query($eventQuery) ?>">
                                                <div class="telemetry-event-top">
                                                    <div class="d-grid gap-1">
                                                        <div class="event-pill"><code><?= esc((string) ($event['event_type'] ?? 'onbekend')) ?></code></div>
                                                        <span class="telemetry-subtle"><?= ! empty($event['created_at']) ? esc(date('d-m-Y H:i:s', strtotime((string) ($event['created_at'])))) : '—' ?></span>
                                                    </div>
                                                    <span class="badge <?= $severityClass ?>"><?= esc((string) ($event['severity'] ?? 'info')) ?></span>
                                                </div>
                                                <div class="telemetry-event-meta">
                                                    <span class="metric-pill"><i class="bi bi-broadcast"></i><?= esc((string) ($event['channel_name'] ?: 'Geen kanaal')) ?></span>
                                                    <span class="metric-pill"><i class="bi bi-skip-forward-circle"></i><?= esc((string) ($event['last_action'] ?: 'Geen actie')) ?></span>
                                                    <span class="metric-pill"><i class="bi bi-play-btn"></i><?= esc((string) ($event['stream_type'] ?: 'unknown')) ?></span>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="telemetry-event-payload">
                                <?php if (empty($selectedEvent)): ?>
                                    <div class="telemetry-empty">Selecteer een event binnen deze fingerprint om de gesaniteerde payload te bekijken.</div>
                                <?php else: ?>
                                    <?php $decoded = json_decode((string) ($selectedEvent['data_json'] ?? '{}'), true) ?: []; ?>
                                    <div class="telemetry-panel" style="border-radius:18px;">
                                        <div class="telemetry-panel-header">
                                            <h6><i class="bi bi-file-earmark-code me-2"></i>Payload detail</h6>
                                        </div>
                                        <div class="telemetry-panel-body">
                                            <dl class="row small mb-3">
                                                <dt class="col-sm-5 text-muted">Type</dt>
                                                <dd class="col-sm-7"><code><?= esc((string) ($selectedEvent['event_type'] ?? 'onbekend')) ?></code></dd>

                                                <dt class="col-sm-5 text-muted">Ontvangen</dt>
                                                <dd class="col-sm-7"><?= ! empty($selectedEvent['created_at']) ? esc(date('d-m-Y H:i:s', strtotime((string) ($selectedEvent['created_at'])))) : '—' ?></dd>

                                                <dt class="col-sm-5 text-muted">Client time</dt>
                                                <dd class="col-sm-7"><?= ! empty($selectedEvent['client_timestamp']) ? esc(date('d-m-Y H:i:s', strtotime((string) ($selectedEvent['client_timestamp'])))) : '—' ?></dd>

                                                <dt class="col-sm-5 text-muted">Device</dt>
                                                <dd class="col-sm-7"><?= esc((string) ($selectedEvent['device_name'] ?: '—')) ?></dd>

                                                <dt class="col-sm-5 text-muted">Android</dt>
                                                <dd class="col-sm-7"><?= esc((string) ($selectedEvent['android_version'] ?: '—')) ?></dd>

                                                <dt class="col-sm-5 text-muted">Kanaal</dt>
                                                <dd class="col-sm-7"><?= esc((string) ($selectedEvent['channel_name'] ?: '—')) ?></dd>
                                            </dl>

                                            <form method="post" action="<?= base_url('admin/telemetry/delete') ?>" onsubmit="return confirm('Dit telemetry event verwijderen?');" class="mb-3">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= esc((string) ($selectedEvent['id'] ?? 0)) ?>">
                                                <input type="hidden" name="q" value="<?= esc($query) ?>">
                                                <input type="hidden" name="type" value="<?= esc($type) ?>">
                                                <input type="hidden" name="severity" value="<?= esc($severity) ?>">
                                                <input type="hidden" name="app_version" value="<?= esc($appVersion) ?>">
                                                <input type="hidden" name="fingerprint" value="<?= esc($selectedFingerprint) ?>">
                                                <input type="hidden" name="page" value="<?= esc((string) $page) ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-trash3 me-1"></i>Verwijder dit event
                                                </button>
                                            </form>

                                            <label class="form-label small text-muted">Gesaniteerde payload</label>
                                            <pre class="telemetry-payload-box"><?= esc(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="telemetry-panel" style="border-radius:18px;">
                                    <div class="telemetry-panel-header">
                                        <h6><i class="bi bi-bar-chart-line me-2"></i>Top event types 24 uur</h6>
                                    </div>
                                    <div class="telemetry-panel-body">
                                        <?php if (empty($overview['topTypes24h'])): ?>
                                            <p class="text-muted mb-0">Nog geen data beschikbaar.</p>
                                        <?php else: ?>
                                            <div class="list-group list-group-flush">
                                                <?php foreach ($overview['topTypes24h'] as $typeRow): ?>
                                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0" style="background:transparent;">
                                                        <code><?= esc((string) ($typeRow['event_type'] ?? 'onbekend')) ?></code>
                                                        <span class="badge bg-secondary"><?= number_format((int) ($typeRow['total'] ?? 0)) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?= $this->endSection() ?>