<?= $this->extend('admin/layout') ?>

<?= $this->section('head') ?>
<style>
    .telemetry-shell {
        display: grid;
        gap: 1rem;
    }
    .telemetry-command-bar {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(360px, .85fr);
        gap: 1rem;
        padding: 1.1rem 1.2rem;
        border-radius: 24px;
        background:
            linear-gradient(135deg, rgba(7, 17, 34, .96), rgba(17, 24, 39, .92)),
            radial-gradient(circle at top right, rgba(14, 165, 233, .18), transparent 35%);
        border: 1px solid rgba(148, 163, 184, .12);
        box-shadow: 0 20px 48px rgba(2, 6, 23, .26);
    }
    .telemetry-command-copy {
        display: grid;
        gap: .8rem;
        align-content: start;
    }
    .telemetry-kicker {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .3rem .65rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, .7);
        border: 1px solid rgba(148, 163, 184, .12);
        color: #bfdbfe;
        font-size: .72rem;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .telemetry-command-title {
        margin: 0;
        font-size: clamp(1.2rem, 2vw, 1.8rem);
        line-height: 1.1;
    }
    .telemetry-command-description {
        margin: 0;
        color: rgba(226, 232, 240, .72);
        max-width: 62ch;
        font-size: .92rem;
    }
    .telemetry-command-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        align-items: center;
        color: rgba(203, 213, 225, .74);
        font-size: .84rem;
    }
    .telemetry-command-meta strong {
        color: #f8fafc;
    }
    .telemetry-command-actions {
        display: grid;
        gap: .85rem;
        align-content: start;
    }
    .telemetry-filter-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }
    .telemetry-input-stack {
        display: grid;
        gap: .35rem;
    }
    .telemetry-input-stack.full {
        grid-column: 1 / -1;
    }
    .telemetry-overview-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .75rem;
    }
    .telemetry-kpi {
        padding: .95rem 1rem;
        border-radius: 18px;
        background: linear-gradient(180deg, rgba(15, 23, 42, .88), rgba(15, 23, 42, .72));
        border: 1px solid rgba(148, 163, 184, .12);
    }
    .telemetry-kpi-label {
        color: rgba(147, 197, 253, .88);
        font-size: .72rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: .45rem;
    }
    .telemetry-kpi-value {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: .25rem;
    }
    .telemetry-kpi-meta {
        color: rgba(203, 213, 225, .62);
        font-size: .8rem;
    }
    .telemetry-layout {
        display: grid;
        grid-template-columns: minmax(330px, 410px) minmax(0, 1fr);
        gap: 1rem;
        align-items: start;
    }
    .telemetry-panel {
        border-radius: 24px;
        background: linear-gradient(180deg, rgba(15, 23, 42, .97), rgba(15, 23, 42, .9));
        border: 1px solid rgba(148, 163, 184, .14);
        box-shadow: 0 16px 36px rgba(2, 6, 23, .18);
    }
    .telemetry-panel-header {
        padding: .95rem 1.1rem;
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
        max-height: 920px;
        overflow: auto;
        padding-right: .25rem;
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
        padding: 1rem 1.1rem 1.1rem;
        border-bottom: 1px solid rgba(148, 163, 184, .11);
        background: linear-gradient(180deg, rgba(15, 23, 42, .98), rgba(15, 23, 42, .9));
        border-radius: 24px 24px 0 0;
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
    .telemetry-type-cloud,
    .telemetry-list-toolbar {
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
        gap: 1rem;
        padding: 1rem 1.1rem 1.1rem;
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
    .telemetry-list-meta {
        color: rgba(203, 213, 225, .62);
        font-size: .84rem;
    }
    .telemetry-list-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 .55rem;
    }
    .telemetry-list-table th {
        padding: 0 .85rem .25rem;
        color: rgba(203, 213, 225, .62);
        font-size: .72rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        font-weight: 700;
    }
    .telemetry-list-table td {
        padding: 0;
        vertical-align: middle;
    }
    .telemetry-row-link {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(90px, .7fr) minmax(80px, .55fr) minmax(80px, .55fr);
        gap: .8rem;
        align-items: center;
        padding: .9rem .95rem;
        border-radius: 16px;
        background: rgba(15, 23, 42, .64);
        border: 1px solid rgba(148, 163, 184, .12);
        text-decoration: none;
        color: inherit;
        transition: border-color .18s ease, transform .18s ease, box-shadow .18s ease;
    }
    .telemetry-row-link:hover {
        transform: translateY(-1px);
        border-color: rgba(96, 165, 250, .38);
        box-shadow: 0 12px 30px rgba(15, 23, 42, .22);
    }
    .telemetry-row-link.active {
        background: linear-gradient(180deg, rgba(29, 78, 216, .24), rgba(15, 23, 42, .82));
        border-color: rgba(96, 165, 250, .52);
        box-shadow: 0 18px 36px rgba(30, 64, 175, .18);
    }
    .telemetry-row-primary {
        min-width: 0;
        display: grid;
        gap: .35rem;
    }
    .telemetry-row-secondary,
    .telemetry-row-metric {
        display: grid;
        gap: .2rem;
    }
    .telemetry-row-secondary strong,
    .telemetry-row-metric strong {
        font-size: .92rem;
        color: #f8fafc;
    }
    .telemetry-row-caption {
        color: rgba(203, 213, 225, .58);
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    .telemetry-row-fingerprint {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        font-weight: 700;
        min-width: 0;
    }
    .telemetry-row-fingerprint code {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .telemetry-row-device {
        color: rgba(226, 232, 240, .82);
        font-size: .86rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .telemetry-row-types {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
    }
    .telemetry-quick-row {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        padding-top: .8rem;
        border-top: 1px solid rgba(148, 163, 184, .11);
    }
    @media (max-width: 1399px) {
        .telemetry-command-bar,
        .telemetry-overview-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .telemetry-command-copy {
            grid-column: 1 / -1;
        }
    }
    @media (max-width: 1199px) {
        .telemetry-command-bar,
        .telemetry-layout,
        .telemetry-detail-grid {
            grid-template-columns: 1fr;
        }
        .telemetry-row-link {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 767px) {
        .telemetry-overview-grid,
        .telemetry-filter-grid {
            grid-template-columns: 1fr;
        }
        .telemetry-panel-header,
        .telemetry-panel-body,
        .telemetry-detail-head,
        .telemetry-detail-grid {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .telemetry-row-link {
            grid-template-columns: 1fr;
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
    <section class="telemetry-command-bar">
        <div class="telemetry-command-copy">
            <span class="telemetry-kicker"><i class="bi bi-fingerprint"></i> Telemetry fingerprints</span>
            <div class="d-grid gap-2">
                <h2 class="telemetry-command-title">Device telemetry browser</h2>
                <p class="telemetry-command-description">Gebruik deze pagina als operationele cockpit: filter ruis weg, vind problematische fingerprints snel terug en werk direct vanuit dezelfde detailweergave.</p>
            </div>
            <div class="telemetry-command-meta">
                <span>Laatst opgeslagen event</span>
                <strong><?= ! empty($overview['latestCreatedAt']) ? esc(date('d-m-Y H:i:s', strtotime((string) ($overview['latestCreatedAt'])))) : 'geen data' ?></strong>
            </div>
        </div>
        <div class="telemetry-command-actions">
            <form method="get" class="telemetry-filter-grid">
                <div class="telemetry-input-stack full">
                    <label class="form-label small text-muted">Zoeken in events</label>
                    <input type="text" name="q" class="form-control" value="<?= esc($query) ?>" placeholder="event, kanaal, actie, data">
                </div>
                <div class="telemetry-input-stack">
                    <label class="form-label small text-muted">Type</label>
                    <input type="text" name="type" class="form-control" value="<?= esc($type) ?>" placeholder="player_error">
                </div>
                <div class="telemetry-input-stack">
                    <label class="form-label small text-muted">Severity</label>
                    <select name="severity" class="form-select">
                        <option value="">Alle</option>
                        <?php foreach (['info', 'warning', 'error'] as $option): ?>
                            <option value="<?= esc($option) ?>" <?= $severity === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="telemetry-input-stack">
                    <label class="form-label small text-muted">App versie</label>
                    <input type="text" name="app_version" class="form-control" value="<?= esc($appVersion) ?>" placeholder="1.0.0">
                </div>
                <div class="telemetry-input-stack" style="align-content:end;">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        <a href="<?= base_url('admin/telemetry') ?>" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
            <div class="telemetry-quick-row">
                <a href="<?= base_url('admin/telemetry/export/csv') . ($selectedFingerprintLinkQuery !== [] ? '?' . http_build_query($selectedFingerprintLinkQuery) : '') ?>" class="btn btn-outline-secondary btn-sm" data-telemetry-export-link="csv">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
                <a href="<?= base_url('admin/telemetry/export/json') . ($selectedFingerprintLinkQuery !== [] ? '?' . http_build_query($selectedFingerprintLinkQuery) : '') ?>" class="btn btn-outline-secondary btn-sm" data-telemetry-export-link="json">
                    <i class="bi bi-filetype-json me-1"></i>Export JSON
                </a>
                <form method="post" action="<?= base_url('admin/telemetry/delete-filtered') ?>" onsubmit="return confirm('Weet je zeker dat je alle gefilterde telemetry events wilt verwijderen?');" class="d-inline-flex gap-2" data-telemetry-delete-filtered-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="q" value="<?= esc($query) ?>">
                    <input type="hidden" name="type" value="<?= esc($type) ?>">
                    <input type="hidden" name="severity" value="<?= esc($severity) ?>">
                    <input type="hidden" name="app_version" value="<?= esc($appVersion) ?>">
                    <input type="hidden" name="group_query" value="<?= esc($groupQuery) ?>" data-telemetry-group-query>
                    <input type="hidden" name="sort" value="<?= esc($groupSort) ?>" data-telemetry-group-sort>
                    <input type="hidden" name="fingerprint" value="<?= esc($selectedFingerprint) ?>" data-telemetry-selected-fingerprint>
                    <button type="submit" class="btn btn-outline-warning btn-sm" data-telemetry-delete-filtered-button <?= ($baseQuery === [] && $selectedFingerprint === '') ? 'disabled' : '' ?>>
                        <i class="bi bi-funnel-fill me-1"></i><span data-telemetry-delete-filtered-label><?= $selectedFingerprint !== '' ? 'Verwijder gekozen fingerprint' : 'Verwijder huidige selectie' ?></span>
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
                <a href="<?= base_url('admin/telemetry?severity=error') ?>" class="btn btn-sm btn-outline-danger">Alle errors</a>
                <a href="<?= base_url('admin/telemetry?type=manual_report') ?>" class="btn btn-sm btn-outline-secondary">Manual reports</a>
                <a href="<?= base_url('admin/telemetry?type=player_rebuffer') ?>" class="btn btn-sm btn-outline-secondary">Rebuffers</a>
                <a href="<?= base_url('admin/telemetry?type=crash') ?>" class="btn btn-sm btn-outline-secondary">Crashes</a>
            </div>
        </div>
    </section>

    <section class="telemetry-overview-grid">
        <div class="telemetry-kpi">
            <div class="telemetry-kpi-label">Events</div>
            <div class="telemetry-kpi-value"><?= number_format((int) ($overview['total'] ?? 0)) ?></div>
            <div class="telemetry-kpi-meta">Alle opgeslagen telemetry events</div>
        </div>
        <div class="telemetry-kpi">
            <div class="telemetry-kpi-label">Fingerprints</div>
            <div class="telemetry-kpi-value"><?= number_format((int) ($overview['uniqueFingerprints'] ?? 0)) ?></div>
            <div class="telemetry-kpi-meta">Unieke device groepen</div>
        </div>
        <div class="telemetry-kpi">
            <div class="telemetry-kpi-label">24 uur</div>
            <div class="telemetry-kpi-value"><?= number_format((int) ($overview['last24h'] ?? 0)) ?></div>
            <div class="telemetry-kpi-meta">Nieuwe events in de laatste 24 uur</div>
        </div>
        <div class="telemetry-kpi">
            <div class="telemetry-kpi-label">Errors</div>
            <div class="telemetry-kpi-value"><?= number_format((int) ($overview['crashes24h'] ?? 0)) ?></div>
            <div class="telemetry-kpi-meta">Crashes en errors in 24 uur</div>
        </div>
    </section>

    <div class="telemetry-layout" id="telemetry-browser">
        <div class="telemetry-panel">
            <div class="telemetry-panel-header d-flex justify-content-between align-items-center">
                <h6><i class="bi bi-person-bounding-box me-2"></i>Fingerprint groepen</h6>
                <span class="badge bg-secondary"><?= number_format($totalFingerprints) ?> groepen</span>
            </div>
            <div class="telemetry-panel-body">
                <form method="get" class="telemetry-list-toolbar">
                    <input type="hidden" name="q" value="<?= esc($query) ?>">
                    <input type="hidden" name="type" value="<?= esc($type) ?>">
                    <input type="hidden" name="severity" value="<?= esc($severity) ?>">
                    <input type="hidden" name="app_version" value="<?= esc($appVersion) ?>">
                    <?php if ($selectedFingerprint !== ''): ?>
                        <input type="hidden" name="fingerprint" value="<?= esc($selectedFingerprint) ?>">
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <label class="form-label small text-muted">Zoek fingerprint of device</label>
                        <input type="text" name="group_query" class="form-control" value="<?= esc($groupQuery) ?>" placeholder="fingerprint, device, app versie, event type">
                    </div>
                    <div style="min-width: 180px;">
                        <label class="form-label small text-muted">Sorteer op</label>
                        <select name="sort" class="form-select">
                            <option value="latest" <?= $groupSort === 'latest' ? 'selected' : '' ?>>Laatst gezien</option>
                            <option value="errors" <?= $groupSort === 'errors' ? 'selected' : '' ?>>Meeste errors</option>
                            <option value="events" <?= $groupSort === 'events' ? 'selected' : '' ?>>Meeste events</option>
                            <option value="device" <?= $groupSort === 'device' ? 'selected' : '' ?>>Device naam</option>
                            <option value="fingerprint" <?= $groupSort === 'fingerprint' ? 'selected' : '' ?>>Fingerprint</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-secondary">Toepassen</button>
                        <?php if ($groupQuery !== '' || $groupSort !== 'latest'): ?>
                            <a href="<?= base_url('admin/telemetry' . ($baseQuery !== [] ? '?' . http_build_query(array_diff_key($baseQuery, ['group_query' => true, 'sort' => true])) : '')) ?>" class="btn btn-outline-secondary">Wis</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="telemetry-list-meta"><?= number_format($totalEvents) ?> events binnen huidige filters</span>
                    <span class="telemetry-list-meta">Compacte lijst voor grote aantallen fingerprints</span>
                </div>
                <?php if (empty($fingerprintGroups)): ?>
                    <div class="telemetry-empty">Geen fingerprint-groepen gevonden voor de huidige filters.</div>
                <?php else: ?>
                    <div class="fingerprint-scroll" data-telemetry-scroll-list>
                        <table class="telemetry-list-table">
                            <thead>
                                <tr>
                                    <th>Fingerprint / Device</th>
                                    <th>Laatst gezien</th>
                                    <th>Errors</th>
                                    <th>Events</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fingerprintGroups as $group): ?>
                                    <?php
                                        $groupFingerprint = (string) ($group['fingerprint_key'] ?? 'unknown');
                                        $groupLabel = $groupFingerprint === 'unknown' ? 'Onbekende fingerprint' : $groupFingerprint;
                                        $fingerprintUrlQuery = array_merge($baseQuery, ['page' => $page, 'fingerprint' => $groupFingerprint]);
                                        $groupTypes = array_filter(array_map('trim', explode(',', (string) ($group['event_types_csv'] ?? ''))));
                                    ?>
                                    <tr>
                                        <td colspan="4">
                                            <a href="<?= current_url() . '?' . http_build_query($fingerprintUrlQuery) ?>" class="telemetry-row-link <?= $selectedFingerprint === $groupFingerprint ? 'active' : '' ?>">
                                                <div class="telemetry-row-primary">
                                                    <div class="telemetry-row-fingerprint">
                                                        <i class="bi bi-fingerprint"></i>
                                                        <code><?= esc($groupLabel) ?></code>
                                                    </div>
                                                    <div class="telemetry-row-device">
                                                        <?= esc((string) ($group['sample_device_name'] ?: 'Onbekend device')) ?><?= ! empty($group['sample_app_version']) ? ' · v' . esc((string) $group['sample_app_version']) : '' ?>
                                                    </div>
                                                    <div class="telemetry-row-types">
                                                        <?php foreach (array_slice($groupTypes, 0, 3) as $eventType): ?>
                                                            <span class="type-pill"><?= esc($eventType) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <div class="telemetry-row-secondary">
                                                    <span class="telemetry-row-caption">Laatste event</span>
                                                    <strong><?= ! empty($group['latest_created_at']) ? esc(date('d-m-Y H:i:s', strtotime((string) ($group['latest_created_at'])))) : '—' ?></strong>
                                                </div>
                                                <div class="telemetry-row-metric">
                                                    <span class="telemetry-row-caption">Errors</span>
                                                    <strong><?= number_format((int) ($group['error_events'] ?? 0)) ?></strong>
                                                </div>
                                                <div class="telemetry-row-metric">
                                                    <span class="telemetry-row-caption">Events</span>
                                                    <strong><?= number_format((int) ($group['total_events'] ?? 0)) ?></strong>
                                                </div>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                    <div class="telemetry-action-row">
                        <a href="<?= base_url('admin/telemetry/export/json') . ($selectedFingerprintLinkQuery !== [] ? '?' . http_build_query($selectedFingerprintLinkQuery) : '') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-filetype-json me-1"></i>Export gekozen fingerprint
                        </a>
                        <form method="post" action="<?= base_url('admin/telemetry/delete-filtered') ?>" onsubmit="return confirm('Weet je zeker dat je alle events van deze fingerprint wilt verwijderen?');" class="d-inline-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="q" value="<?= esc($query) ?>">
                            <input type="hidden" name="type" value="<?= esc($type) ?>">
                            <input type="hidden" name="severity" value="<?= esc($severity) ?>">
                            <input type="hidden" name="app_version" value="<?= esc($appVersion) ?>">
                            <input type="hidden" name="group_query" value="<?= esc($groupQuery) ?>">
                            <input type="hidden" name="sort" value="<?= esc($groupSort) ?>">
                            <input type="hidden" name="fingerprint" value="<?= esc($selectedFingerprint) ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash3 me-1"></i>Verwijder hele fingerprint
                            </button>
                        </form>
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
                                    <input type="hidden" name="group_query" value="<?= esc($groupQuery) ?>">
                                    <input type="hidden" name="sort" value="<?= esc($groupSort) ?>">
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

<?= $this->section('scripts') ?>
<script>
    (() => {
        let isNavigating = false;

        const exportBaseUrls = {
            csv: <?= json_encode(base_url('admin/telemetry/export/csv'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            json: <?= json_encode(base_url('admin/telemetry/export/json'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
        };
        const selectedFingerprint = <?= json_encode($selectedFingerprint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const hasBaseFilters = <?= json_encode($baseQuery !== []) ?>;
        const selectedGroupQuery = <?= json_encode($groupQuery, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const selectedGroupSort = <?= json_encode($groupSort, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

        const getTelemetryRoot = () => document.getElementById('telemetry-browser');
        const getFingerprintList = (root = document) => root.querySelector('[data-telemetry-scroll-list]');
        const getDeleteFilteredFingerprintInput = () => document.querySelector('[data-telemetry-selected-fingerprint]');
        const getDeleteFilteredButton = () => document.querySelector('[data-telemetry-delete-filtered-button]');
        const getDeleteFilteredLabel = () => document.querySelector('[data-telemetry-delete-filtered-label]');
        const getGroupQueryInputs = () => document.querySelectorAll('[data-telemetry-group-query]');
        const getGroupSortInputs = () => document.querySelectorAll('[data-telemetry-group-sort]');
        const getExportLink = (kind) => document.querySelector(`[data-telemetry-export-link="${kind}"]`);

        const syncTopActionState = (fingerprint, baseQuery, hasFilters, groupQuery, groupSort) => {
            const fingerprintValue = typeof fingerprint === 'string' ? fingerprint : '';
            const query = baseQuery && typeof baseQuery === 'object' ? { ...baseQuery } : {};
            if (fingerprintValue !== '') {
                query.fingerprint = fingerprintValue;
            }
            if (groupQuery) {
                query.group_query = groupQuery;
            }
            if (groupSort && groupSort !== 'latest') {
                query.sort = groupSort;
            }

            const csvLink = getExportLink('csv');
            if (csvLink) {
                csvLink.href = exportBaseUrls.csv + (Object.keys(query).length > 0 ? `?${new URLSearchParams(query).toString()}` : '');
            }

            const jsonLink = getExportLink('json');
            if (jsonLink) {
                jsonLink.href = exportBaseUrls.json + (Object.keys(query).length > 0 ? `?${new URLSearchParams(query).toString()}` : '');
            }

            const fingerprintInput = getDeleteFilteredFingerprintInput();
            if (fingerprintInput) {
                fingerprintInput.value = fingerprintValue;
            }

            getGroupQueryInputs().forEach((input) => {
                input.value = groupQuery || '';
            });
            getGroupSortInputs().forEach((input) => {
                input.value = groupSort || 'latest';
            });

            const deleteButton = getDeleteFilteredButton();
            if (deleteButton) {
                deleteButton.disabled = !hasFilters && fingerprintValue === '';
            }

            const deleteLabel = getDeleteFilteredLabel();
            if (deleteLabel) {
                deleteLabel.textContent = fingerprintValue !== '' ? 'Verwijder gekozen fingerprint' : 'Verwijder huidige selectie';
            }
        };

        const extractNavigationState = (url, root) => {
            const nextUrl = new URL(url, window.location.origin);
            const nextBaseQuery = {};
            for (const [key, value] of nextUrl.searchParams.entries()) {
                if (['q', 'type', 'severity', 'app_version'].includes(key) && value !== '') {
                    nextBaseQuery[key] = value;
                }
            }

            const nextFingerprintInput = root.querySelector('[name="fingerprint"]');
            return {
                fingerprint: nextFingerprintInput instanceof HTMLInputElement ? nextFingerprintInput.value : (nextUrl.searchParams.get('fingerprint') || ''),
                baseQuery: nextBaseQuery,
                hasFilters: Object.keys(nextBaseQuery).length > 0,
                groupQuery: nextUrl.searchParams.get('group_query') || '',
                groupSort: nextUrl.searchParams.get('sort') || 'latest',
            };
        };

        const isTelemetryNavigationLink = (link) => {
            if (!(link instanceof HTMLAnchorElement)) {
                return false;
            }

            if (link.target && link.target !== '_self') {
                return false;
            }

            const telemetryRoot = getTelemetryRoot();
            if (!telemetryRoot || !telemetryRoot.contains(link)) {
                return false;
            }

            const url = new URL(link.href, window.location.origin);
            return url.origin === window.location.origin && url.pathname === window.location.pathname;
        };

        const swapTelemetryRoot = (nextRoot, listScrollTop) => {
            const currentRoot = getTelemetryRoot();
            if (!currentRoot || !nextRoot) {
                return;
            }

            currentRoot.replaceWith(nextRoot);
            const nextList = getFingerprintList(nextRoot);
            if (nextList) {
                nextList.scrollTop = listScrollTop;
            }
        };

        const navigateTelemetry = async (url, pushState = true) => {
            const currentRoot = getTelemetryRoot();
            if (!currentRoot || isNavigating) {
                return;
            }

            isNavigating = true;
            const currentList = getFingerprintList(currentRoot);
            const listScrollTop = currentList ? currentList.scrollTop : 0;
            const currentHeight = currentRoot.offsetHeight;
            currentRoot.style.minHeight = `${currentHeight}px`;
            currentRoot.style.opacity = '0.6';
            currentRoot.style.pointerEvents = 'none';

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Telemetry navigation failed with status ${response.status}`);
                }

                const html = await response.text();
                const documentFragment = new DOMParser().parseFromString(html, 'text/html');
                const nextRoot = documentFragment.getElementById('telemetry-browser');

                if (!nextRoot) {
                    throw new Error('Telemetry browser fragment not found in response');
                }

                swapTelemetryRoot(nextRoot, listScrollTop);

                const nextState = extractNavigationState(url, nextRoot);
                syncTopActionState(nextState.fingerprint, nextState.baseQuery, nextState.hasFilters, nextState.groupQuery, nextState.groupSort);

                if (pushState) {
                    window.history.pushState({ telemetryUrl: url }, '', url);
                }
            } catch (error) {
                console.debug('Falling back to full telemetry navigation', error);
                window.location.assign(url);
            } finally {
                const updatedRoot = getTelemetryRoot();
                if (updatedRoot) {
                    updatedRoot.style.minHeight = '';
                    updatedRoot.style.opacity = '';
                    updatedRoot.style.pointerEvents = '';
                }
                isNavigating = false;
            }
        };

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const link = target.closest('a');
            if (!isTelemetryNavigationLink(link)) {
                return;
            }

            event.preventDefault();
            navigateTelemetry(link.href);
        });

        window.addEventListener('popstate', () => {
            if (!window.location.pathname.includes('/admin/telemetry')) {
                return;
            }

            navigateTelemetry(window.location.href, false);
        });

        syncTopActionState(selectedFingerprint, <?= json_encode($baseQuery, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>, hasBaseFilters, selectedGroupQuery, selectedGroupSort);
    })();
</script>
<?= $this->endSection() ?>