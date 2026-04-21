<?= $this->extend('admin/layout') ?>

<?= $this->section('head') ?>
<style>
    .telemetry-page {
        display: grid;
        gap: 1rem;
    }
    .telemetry-page.is-loading {
        opacity: .72;
        transition: opacity .18s ease;
    }
    .telemetry-command {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(420px, .95fr);
        gap: 1rem;
        padding: 1.1rem 1.2rem;
        border-radius: 24px;
        border: 1px solid rgba(148, 163, 184, .12);
        background:
            radial-gradient(circle at top right, rgba(14, 165, 233, .18), transparent 35%),
            linear-gradient(135deg, rgba(8, 15, 30, .96), rgba(15, 23, 42, .93));
        box-shadow: 0 22px 52px rgba(2, 6, 23, .24);
    }
    .telemetry-command-copy {
        display: grid;
        gap: .75rem;
        align-content: start;
    }
    .telemetry-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .3rem .65rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, .72);
        border: 1px solid rgba(148, 163, 184, .1);
        color: #bfdbfe;
        font-size: .72rem;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .telemetry-command-title {
        margin: 0;
        font-size: clamp(1.3rem, 2vw, 1.9rem);
        line-height: 1.08;
    }
    .telemetry-command-text {
        margin: 0;
        color: rgba(226, 232, 240, .72);
        max-width: 62ch;
        font-size: .92rem;
    }
    .telemetry-command-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        align-items: center;
        color: rgba(203, 213, 225, .68);
        font-size: .84rem;
    }
    .telemetry-command-meta strong {
        color: #f8fafc;
    }
    .telemetry-filter-form {
        display: grid;
        gap: .8rem;
        align-content: start;
    }
    .telemetry-filter-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }
    .telemetry-field {
        display: grid;
        gap: .35rem;
    }
    .telemetry-field.full {
        grid-column: 1 / -1;
    }
    .telemetry-filter-actions {
        display: flex;
        gap: .6rem;
    }
    .telemetry-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .75rem;
    }
    .telemetry-kpi {
        padding: .95rem 1rem;
        border-radius: 18px;
        background: linear-gradient(180deg, rgba(15, 23, 42, .92), rgba(15, 23, 42, .76));
        border: 1px solid rgba(148, 163, 184, .12);
    }
    .telemetry-kpi-label {
        color: rgba(147, 197, 253, .86);
        font-size: .72rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: .4rem;
    }
    .telemetry-kpi-value {
        font-size: 1.55rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: .25rem;
    }
    .telemetry-kpi-meta {
        color: rgba(203, 213, 225, .62);
        font-size: .8rem;
    }
    .telemetry-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: .8rem;
        padding: .95rem 1.05rem;
        border-radius: 20px;
        background: linear-gradient(180deg, rgba(15, 23, 42, .96), rgba(15, 23, 42, .9));
        border: 1px solid rgba(148, 163, 184, .12);
    }
    .telemetry-toolbar-group {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
        align-items: center;
    }
    .telemetry-table-panel {
        border-radius: 24px;
        background: linear-gradient(180deg, rgba(15, 23, 42, .98), rgba(15, 23, 42, .91));
        border: 1px solid rgba(148, 163, 184, .12);
        box-shadow: 0 16px 36px rgba(2, 6, 23, .18);
        overflow: hidden;
    }
    .telemetry-table-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.1rem;
        border-bottom: 1px solid rgba(148, 163, 184, .1);
    }
    .telemetry-table-heading {
        display: grid;
        gap: .3rem;
    }
    .telemetry-table-heading h3 {
        margin: 0;
        font-size: 1.08rem;
    }
    .telemetry-table-heading p {
        margin: 0;
        color: rgba(203, 213, 225, .62);
        font-size: .84rem;
    }
    .telemetry-table-tools {
        display: grid;
        grid-template-columns: minmax(250px, 1fr) minmax(180px, .55fr) minmax(120px, .32fr) auto;
        gap: .65rem;
        align-items: end;
        min-width: min(100%, 760px);
    }
    .telemetry-table-wrap {
        overflow: auto;
    }
    .telemetry-table {
        width: 100%;
        border-collapse: collapse;
    }
    .telemetry-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: rgba(9, 14, 27, .94);
        color: rgba(203, 213, 225, .6);
        font-size: .72rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        text-align: left;
        padding: .85rem 1rem;
        border-bottom: 1px solid rgba(148, 163, 184, .08);
        white-space: nowrap;
    }
    .telemetry-table tbody tr {
        cursor: pointer;
        transition: background .16s ease;
    }
    .telemetry-table tbody tr:hover {
        background: rgba(30, 41, 59, .42);
    }
    .telemetry-table tbody tr.is-active {
        background: linear-gradient(90deg, rgba(8, 47, 73, .44), rgba(15, 23, 42, .96));
        box-shadow: inset 3px 0 0 rgba(56, 189, 248, .8);
    }
    .telemetry-table tbody td {
        padding: .95rem 1rem;
        border-bottom: 1px solid rgba(148, 163, 184, .08);
        vertical-align: middle;
    }
    .telemetry-cell-stack {
        display: grid;
        gap: .28rem;
        min-width: 0;
    }
    .telemetry-fingerprint {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        min-width: 0;
        font-weight: 700;
    }
    .telemetry-fingerprint code {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        background: rgba(30, 41, 59, .92);
        color: #dbeafe;
    }
    .telemetry-subline {
        color: rgba(226, 232, 240, .76);
        font-size: .84rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .telemetry-muted {
        color: rgba(203, 213, 225, .6);
        font-size: .82rem;
    }
    .telemetry-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .65rem;
        border-radius: 999px;
        background: rgba(30, 41, 59, .72);
        border: 1px solid rgba(148, 163, 184, .1);
        color: #dbeafe;
        font-size: .76rem;
        white-space: nowrap;
    }
    .telemetry-pill.error {
        background: rgba(127, 29, 29, .48);
        color: #fecaca;
        border-color: rgba(248, 113, 113, .2);
    }
    .telemetry-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .8rem;
        padding: .85rem 1rem;
        border-top: 1px solid rgba(148, 163, 184, .08);
    }
    .telemetry-pagination-meta {
        color: rgba(203, 213, 225, .6);
        font-size: .82rem;
    }
    .telemetry-row-actions {
        display: flex;
        justify-content: flex-end;
        gap: .45rem;
        white-space: nowrap;
    }
    .telemetry-row-actions .btn {
        white-space: nowrap;
    }
    .telemetry-killswitch {
        opacity: .58;
    }
    .telemetry-drawer-shell {
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 1045;
    }
    .telemetry-drawer-shell.is-visible {
        pointer-events: auto;
    }
    .telemetry-drawer-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(2, 6, 23, .56);
        opacity: 0;
        transition: opacity .22s ease;
    }
    .telemetry-drawer-shell.is-visible .telemetry-drawer-backdrop {
        opacity: 1;
    }
    .telemetry-drawer {
        position: absolute;
        top: 0;
        right: 0;
        width: min(860px, calc(100vw - 24px));
        height: 100%;
        display: grid;
        grid-template-rows: auto auto 1fr;
        background: linear-gradient(180deg, rgba(10, 16, 28, .995), rgba(15, 23, 42, .99));
        border-left: 1px solid rgba(148, 163, 184, .12);
        box-shadow: -24px 0 60px rgba(2, 6, 23, .35);
        transform: translateX(105%);
        transition: transform .26s ease;
    }
    .telemetry-drawer-shell.is-visible .telemetry-drawer {
        transform: translateX(0);
    }
    .telemetry-drawer-head {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.15rem 1.2rem 1rem;
        border-bottom: 1px solid rgba(148, 163, 184, .09);
    }
    .telemetry-drawer-head h3 {
        margin: 0;
        font-size: 1.15rem;
    }
    .telemetry-drawer-head p {
        margin: .3rem 0 0;
        color: rgba(203, 213, 225, .64);
        font-size: .84rem;
    }
    .telemetry-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: rgba(30, 41, 59, .72);
        border: 1px solid rgba(148, 163, 184, .1);
        color: #f8fafc;
        text-decoration: none;
        font-size: 1.1rem;
    }
    .telemetry-drawer-meta,
    .telemetry-drawer-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
        padding: .9rem 1.2rem 0;
    }
    .telemetry-chip {
        display: inline-flex;
        align-items: center;
        gap: .38rem;
        padding: .5rem .75rem;
        border-radius: 14px;
        background: rgba(30, 41, 59, .72);
        border: 1px solid rgba(148, 163, 184, .1);
        color: #dbeafe;
        font-size: .82rem;
    }
    .telemetry-chip strong {
        color: #fff;
    }
    .telemetry-drawer-body {
        display: grid;
        grid-template-columns: minmax(0, .9fr) minmax(320px, .8fr);
        gap: 1rem;
        min-height: 0;
        padding: 1rem 1.2rem 1.2rem;
    }
    .telemetry-drawer-column {
        display: grid;
        gap: .8rem;
        min-height: 0;
        align-content: start;
    }
    .telemetry-drawer-section {
        border-radius: 20px;
        background: rgba(15, 23, 42, .72);
        border: 1px solid rgba(148, 163, 184, .1);
        overflow: hidden;
    }
    .telemetry-drawer-section-head {
        display: flex;
        justify-content: space-between;
        gap: .8rem;
        align-items: center;
        padding: .85rem 1rem;
        border-bottom: 1px solid rgba(148, 163, 184, .08);
    }
    .telemetry-drawer-section-head h4 {
        margin: 0;
        font-size: .95rem;
    }
    .telemetry-events {
        display: grid;
        gap: .75rem;
        max-height: calc(100vh - 300px);
        overflow: auto;
        padding: 1rem;
    }
    .telemetry-event-card {
        display: grid;
        gap: .65rem;
        padding: .9rem;
        border-radius: 16px;
        background: rgba(15, 23, 42, .68);
        border: 1px solid rgba(148, 163, 184, .1);
        text-decoration: none;
        color: inherit;
        transition: border-color .16s ease, transform .16s ease;
    }
    .telemetry-event-card:hover {
        transform: translateY(-1px);
        border-color: rgba(96, 165, 250, .34);
    }
    .telemetry-event-card.is-active {
        border-color: rgba(56, 189, 248, .46);
        background: linear-gradient(180deg, rgba(8, 47, 73, .34), rgba(15, 23, 42, .82));
    }
    .telemetry-event-top,
    .telemetry-event-meta {
        display: flex;
        justify-content: space-between;
        gap: .6rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .telemetry-payload-box {
        margin: 0;
        padding: 1rem;
        border-radius: 16px;
        background: rgba(2, 6, 23, .9);
        border: 1px solid rgba(30, 41, 59, .95);
        color: #dbeafe;
        white-space: pre-wrap;
        word-break: break-word;
        max-height: 360px;
        overflow: auto;
        font-size: .86rem;
        line-height: 1.42;
    }
    .telemetry-empty {
        padding: 1.25rem;
        border-radius: 18px;
        background: rgba(15, 23, 42, .56);
        border: 1px dashed rgba(148, 163, 184, .14);
        color: rgba(203, 213, 225, .66);
        text-align: center;
    }
    @media (max-width: 1399px) {
        .telemetry-command {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 1199px) {
        .telemetry-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .telemetry-drawer {
            width: min(920px, calc(100vw - 16px));
        }
        .telemetry-drawer-body {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 767px) {
        .telemetry-command,
        .telemetry-toolbar,
        .telemetry-table-header,
        .telemetry-drawer-head,
        .telemetry-drawer-meta,
        .telemetry-drawer-actions,
        .telemetry-drawer-body {
            padding-left: .9rem;
            padding-right: .9rem;
        }
        .telemetry-summary,
        .telemetry-filter-grid,
        .telemetry-table-tools {
            grid-template-columns: 1fr;
        }
        .telemetry-pagination {
            flex-direction: column;
            align-items: stretch;
        }
        .telemetry-drawer {
            width: 100%;
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
    $drawerCloseQuery = $baseQuery;
    if ($page > 1) {
        $drawerCloseQuery['page'] = (string) $page;
    }
    $drawerCloseUrl = current_url() . ($drawerCloseQuery !== [] ? '?' . http_build_query($drawerCloseQuery) : '');
    $hasDrawer = ! empty($selectedFingerprintSummary);
    $hasGlobalDeleteScope = $query !== '' || $type !== '' || $severity !== '' || $appVersion !== '';
    $pageStart = $totalFingerprints > 0 ? (($page - 1) * $perPage) + 1 : 0;
    $pageEnd = min($totalFingerprints, $page * $perPage);
?>

<div class="telemetry-page" id="telemetry-page">
    <section class="telemetry-command">
        <div class="telemetry-command-copy">
            <span class="telemetry-eyebrow"><i class="bi bi-grid-1x2"></i> Telemetry console</span>
            <h2 class="telemetry-command-title">Fingerprint browser</h2>
            <p class="telemetry-command-text">Een strakke operations-view voor devicegroepen, foutpatronen en payload-inspectie. Klik een fingerprint in de tabel en werk verder in de slide-in drawer rechts.</p>
            <div class="telemetry-command-meta">
                <span>Laatst opgeslagen event</span>
                <strong><?= ! empty($overview['latestCreatedAt']) ? esc(date('d-m-Y H:i:s', strtotime((string) ($overview['latestCreatedAt'])))) : 'geen data' ?></strong>
            </div>
        </div>

        <form method="get" class="telemetry-filter-form" data-telemetry-form>
            <input type="hidden" name="group_query" value="<?= esc($groupQuery) ?>">
            <input type="hidden" name="sort" value="<?= esc($groupSort) ?>">
            <input type="hidden" name="per_page" value="<?= esc((string) $perPage) ?>">
            <input type="hidden" name="page" value="1">
            <div class="telemetry-filter-grid">
                <div class="telemetry-field full">
                    <label class="form-label small text-muted">Zoeken in events</label>
                    <input type="text" name="q" class="form-control" value="<?= esc($query) ?>" placeholder="event, kanaal, actie, data">
                </div>
                <div class="telemetry-field">
                    <label class="form-label small text-muted">Type</label>
                    <input type="text" name="type" class="form-control" value="<?= esc($type) ?>" placeholder="player_error">
                </div>
                <div class="telemetry-field">
                    <label class="form-label small text-muted">Severity</label>
                    <select name="severity" class="form-select">
                        <option value="">Alle</option>
                        <?php foreach (['info', 'warning', 'error'] as $option): ?>
                            <option value="<?= esc($option) ?>" <?= $severity === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="telemetry-field">
                    <label class="form-label small text-muted">App versie</label>
                    <input type="text" name="app_version" class="form-control" value="<?= esc($appVersion) ?>" placeholder="1.0.0">
                </div>
                <div class="telemetry-field" style="align-content:end;">
                    <div class="telemetry-filter-actions">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        <a href="<?= base_url('admin/telemetry') ?>" class="btn btn-outline-secondary" data-telemetry-nav>Reset</a>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <section class="telemetry-summary">
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

    <section class="telemetry-toolbar">
        <div class="telemetry-toolbar-group">
            <a href="<?= base_url('admin/telemetry/export/csv') . ($baseQuery !== [] ? '?' . http_build_query($baseQuery) : '') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-download me-1"></i>Export CSV
            </a>
            <a href="<?= base_url('admin/telemetry/export/json') . ($baseQuery !== [] ? '?' . http_build_query($baseQuery) : '') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-filetype-json me-1"></i>Export JSON
            </a>
            <form method="post" action="<?= base_url('admin/telemetry/delete-filtered') ?>" onsubmit="return confirm('Weet je zeker dat je alle gefilterde telemetry events wilt verwijderen?');" class="d-inline-flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="q" value="<?= esc($query) ?>">
                <input type="hidden" name="type" value="<?= esc($type) ?>">
                <input type="hidden" name="severity" value="<?= esc($severity) ?>">
                <input type="hidden" name="app_version" value="<?= esc($appVersion) ?>">
                <input type="hidden" name="group_query" value="<?= esc($groupQuery) ?>">
                <input type="hidden" name="sort" value="<?= esc($groupSort) ?>">
                <input type="hidden" name="per_page" value="<?= esc((string) $perPage) ?>">
                <input type="hidden" name="page" value="<?= esc((string) $page) ?>">
                <button type="submit" class="btn btn-outline-warning btn-sm" <?= ! $hasGlobalDeleteScope ? 'disabled' : '' ?>>
                    <i class="bi bi-funnel-fill me-1"></i>Verwijder gefilterd
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
        <div class="telemetry-toolbar-group">
            <a href="<?= base_url('admin/telemetry?severity=error') ?>" class="btn btn-sm btn-outline-danger" data-telemetry-nav>Alle errors</a>
            <a href="<?= base_url('admin/telemetry?type=manual_report') ?>" class="btn btn-sm btn-outline-secondary" data-telemetry-nav>Manual reports</a>
            <a href="<?= base_url('admin/telemetry?type=player_rebuffer') ?>" class="btn btn-sm btn-outline-secondary" data-telemetry-nav>Rebuffers</a>
            <a href="<?= base_url('admin/telemetry?type=crash') ?>" class="btn btn-sm btn-outline-secondary" data-telemetry-nav>Crashes</a>
        </div>
    </section>

    <section class="telemetry-table-panel" id="telemetry-browser">
        <div class="telemetry-table-header">
            <div class="telemetry-table-heading">
                <h3>Fingerprints</h3>
                <p>Server-side paging actief. <?= $totalFingerprints > 0 ? number_format($pageStart) . '–' . number_format($pageEnd) : '0' ?> van <?= number_format($totalFingerprints) ?> groepen op deze paginaweergave.</p>
            </div>

            <form method="get" class="telemetry-table-tools" data-telemetry-form>
                <input type="hidden" name="q" value="<?= esc($query) ?>">
                <input type="hidden" name="type" value="<?= esc($type) ?>">
                <input type="hidden" name="severity" value="<?= esc($severity) ?>">
                <input type="hidden" name="app_version" value="<?= esc($appVersion) ?>">
                <input type="hidden" name="page" value="1">
                <div class="telemetry-field">
                    <label class="form-label small text-muted">Zoek fingerprint of device</label>
                    <input type="text" name="group_query" class="form-control" value="<?= esc($groupQuery) ?>" placeholder="fingerprint, device, app versie, event type">
                </div>
                <div class="telemetry-field">
                    <label class="form-label small text-muted">Sorteer op</label>
                    <select name="sort" class="form-select">
                        <option value="latest" <?= $groupSort === 'latest' ? 'selected' : '' ?>>Laatst gezien</option>
                        <option value="errors" <?= $groupSort === 'errors' ? 'selected' : '' ?>>Meeste errors</option>
                        <option value="events" <?= $groupSort === 'events' ? 'selected' : '' ?>>Meeste events</option>
                        <option value="device" <?= $groupSort === 'device' ? 'selected' : '' ?>>Device naam</option>
                        <option value="fingerprint" <?= $groupSort === 'fingerprint' ? 'selected' : '' ?>>Fingerprint</option>
                    </select>
                </div>
                <div class="telemetry-field">
                    <label class="form-label small text-muted">Per pagina</label>
                    <select name="per_page" class="form-select">
                        <?php foreach ($perPageOptions as $option): ?>
                            <option value="<?= esc((string) $option) ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= esc((string) $option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="telemetry-filter-actions">
                    <button type="submit" class="btn btn-outline-secondary">Toepassen</button>
                    <?php if ($groupQuery !== '' || $groupSort !== 'latest' || $perPage !== 25): ?>
                        <?php
                            $clearGroupQuery = $baseQuery;
                            unset($clearGroupQuery['group_query'], $clearGroupQuery['sort'], $clearGroupQuery['per_page']);
                        ?>
                        <a href="<?= current_url() . ($clearGroupQuery !== [] ? '?' . http_build_query($clearGroupQuery) : '') ?>" class="btn btn-outline-secondary" data-telemetry-nav>Wis</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="telemetry-table-wrap">
            <?php if (empty($fingerprintGroups)): ?>
                <div class="telemetry-empty" style="margin: 1rem;">Geen fingerprint-groepen gevonden voor de huidige filters.</div>
            <?php else: ?>
                <table class="telemetry-table">
                    <thead>
                        <tr>
                            <th>Fingerprint / Device</th>
                            <th>App</th>
                            <th>Laatst gezien</th>
                            <th>Errors</th>
                            <th>Warnings</th>
                            <th>Events</th>
                            <th>Types</th>
                            <th class="text-end">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fingerprintGroups as $group): ?>
                            <?php
                                $groupFingerprint = (string) ($group['fingerprint_key'] ?? 'unknown');
                                $groupLabel = $groupFingerprint === 'unknown' ? 'Onbekende fingerprint' : $groupFingerprint;
                                $fingerprintUrlQuery = array_merge($baseQuery, ['page' => $page, 'fingerprint' => $groupFingerprint]);
                            ?>
                            <tr class="<?= $selectedFingerprint === $groupFingerprint ? 'is-active' : '' ?>" data-telemetry-row data-url="<?= esc(current_url() . '?' . http_build_query($fingerprintUrlQuery), 'attr') ?>">
                                <td>
                                    <div class="telemetry-cell-stack">
                                        <div class="telemetry-fingerprint">
                                            <i class="bi bi-fingerprint"></i>
                                            <code title="<?= esc($groupLabel) ?>"><?= esc($groupLabel) ?></code>
                                        </div>
                                        <div class="telemetry-subline"><?= esc((string) ($group['sample_device_name'] ?: 'Onbekend device')) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="telemetry-cell-stack">
                                        <div><?= esc((string) ($group['sample_app_version'] ?: 'Onbekend')) ?></div>
                                        <div class="telemetry-muted"><?= number_format((int) ($group['unique_channels'] ?? 0)) ?> kanalen</div>
                                    </div>
                                </td>
                                <td><?= ! empty($group['latest_created_at']) ? esc(date('d-m-Y H:i:s', strtotime((string) ($group['latest_created_at'])))) : '—' ?></td>
                                <td><span class="telemetry-pill error"><?= number_format((int) ($group['error_events'] ?? 0)) ?></span></td>
                                <td><span class="telemetry-pill"><?= number_format((int) ($group['warning_events'] ?? 0)) ?></span></td>
                                <td><span class="telemetry-pill"><?= number_format((int) ($group['total_events'] ?? 0)) ?></span></td>
                                <td><span class="telemetry-pill"><?= number_format((int) ($group['unique_event_types'] ?? 0)) ?></span></td>
                                <td>
                                    <div class="telemetry-row-actions">
                                        <form method="post" action="<?= base_url('admin/telemetry/delete-filtered') ?>" onsubmit="return confirm('Weet je zeker dat je alle events van deze fingerprint wilt verwijderen?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="group_query" value="<?= esc($groupQuery) ?>">
                                            <input type="hidden" name="sort" value="<?= esc($groupSort) ?>">
                                            <input type="hidden" name="per_page" value="<?= esc((string) $perPage) ?>">
                                            <input type="hidden" name="page" value="<?= esc((string) $page) ?>">
                                            <input type="hidden" name="fingerprint" value="<?= esc($groupFingerprint) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash3 me-1"></i>Delete
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-secondary telemetry-killswitch" disabled title="Killswitch placeholder">
                                            <i class="bi bi-power me-1"></i>Killswitch
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="telemetry-pagination">
                <div class="telemetry-pagination-meta">Pagina <?= $page ?> van <?= $totalPages ?></div>
                <ul class="pagination pagination-sm mb-0">
                    <?php
                        $previousQuery = http_build_query(array_merge($baseQuery, ['page' => max(1, $page - 1)]));
                        $nextQuery = http_build_query(array_merge($baseQuery, ['page' => min($totalPages, $page + 1)]));
                    ?>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page <= 1 ? '#' : current_url() . '?' . $previousQuery ?>" data-telemetry-nav>Vorige</a>
                    </li>
                    <?php for ($currentPage = max(1, $page - 2); $currentPage <= min($totalPages, $page + 2); $currentPage++): ?>
                        <li class="page-item <?= $currentPage === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= current_url() . '?' . http_build_query(array_merge($baseQuery, ['page' => $currentPage])) ?>" data-telemetry-nav><?= $currentPage ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page >= $totalPages ? '#' : current_url() . '?' . $nextQuery ?>" data-telemetry-nav>Volgende</a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($hasDrawer): ?>
        <div class="telemetry-drawer-shell" data-telemetry-drawer-shell data-open="1">
            <a href="<?= $drawerCloseUrl ?>" class="telemetry-drawer-backdrop" data-telemetry-close data-telemetry-nav aria-label="Sluit detailweergave"></a>
            <aside class="telemetry-drawer" role="dialog" aria-modal="true" aria-label="Fingerprint details">
                <?php $selectedTypes = array_filter(array_map('trim', explode(',', (string) ($selectedFingerprintSummary['event_types_csv'] ?? '')))); ?>
                <div class="telemetry-drawer-head">
                    <div>
                        <h3><?= esc($selectedFingerprintLabel) ?></h3>
                        <p>Alle events van deze fingerprint, direct vanuit een slide-in detailweergave.</p>
                    </div>
                    <a href="<?= $drawerCloseUrl ?>" class="telemetry-close" data-telemetry-close data-telemetry-nav aria-label="Sluiten"><i class="bi bi-x-lg"></i></a>
                </div>

                <div class="telemetry-drawer-meta">
                    <span class="telemetry-chip"><i class="bi bi-phone"></i><strong><?= esc((string) ($selectedFingerprintSummary['sample_device_name'] ?: 'Onbekend device')) ?></strong></span>
                    <span class="telemetry-chip"><i class="bi bi-box"></i><strong><?= esc((string) ($selectedFingerprintSummary['sample_app_version'] ?: 'Onbekende versie')) ?></strong></span>
                    <span class="telemetry-chip"><i class="bi bi-calendar-event"></i><strong><?= ! empty($selectedFingerprintSummary['latest_created_at']) ? esc(date('d-m-Y H:i:s', strtotime((string) ($selectedFingerprintSummary['latest_created_at'])))) : '—' ?></strong></span>
                    <span class="telemetry-chip"><strong><?= number_format((int) ($selectedFingerprintSummary['total_events'] ?? 0)) ?></strong> events</span>
                    <span class="telemetry-chip"><strong><?= number_format((int) ($selectedFingerprintSummary['error_events'] ?? 0)) ?></strong> errors</span>
                    <span class="telemetry-chip"><strong><?= number_format((int) ($selectedFingerprintSummary['warning_events'] ?? 0)) ?></strong> warnings</span>
                </div>

                <div class="telemetry-drawer-actions">
                    <a href="<?= base_url('admin/telemetry/export/json') . ($selectedFingerprintLinkQuery !== [] ? '?' . http_build_query($selectedFingerprintLinkQuery) : '') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-filetype-json me-1"></i>Export fingerprint
                    </a>
                    <form method="post" action="<?= base_url('admin/telemetry/delete-filtered') ?>" onsubmit="return confirm('Weet je zeker dat je alle events van deze fingerprint wilt verwijderen?');" class="d-inline-flex gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="group_query" value="<?= esc($groupQuery) ?>">
                        <input type="hidden" name="sort" value="<?= esc($groupSort) ?>">
                        <input type="hidden" name="per_page" value="<?= esc((string) $perPage) ?>">
                        <input type="hidden" name="page" value="<?= esc((string) $page) ?>">
                        <input type="hidden" name="fingerprint" value="<?= esc($selectedFingerprint) ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash3 me-1"></i>Verwijder fingerprint
                        </button>
                    </form>
                    <?php foreach (array_slice($selectedTypes, 0, 4) as $eventType): ?>
                        <span class="telemetry-pill"><?= esc($eventType) ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="telemetry-drawer-body">
                    <div class="telemetry-drawer-column">
                        <section class="telemetry-drawer-section">
                            <div class="telemetry-drawer-section-head">
                                <h4>Events</h4>
                                <span class="telemetry-muted"><?= count($selectedFingerprintEvents) ?> geladen</span>
                            </div>
                            <div class="telemetry-events">
                                <?php foreach ($selectedFingerprintEvents as $event): ?>
                                    <?php
                                        $eventQuery = array_merge($selectedFingerprintLinkQuery, [
                                            'page' => $page,
                                            'id' => $event['id'],
                                        ]);
                                        $severityClass = match ($event['severity'] ?? '') {
                                            'error' => 'telemetry-pill error',
                                            'warning' => 'telemetry-pill',
                                            default => 'telemetry-pill',
                                        };
                                    ?>
                                    <a href="<?= current_url() . '?' . http_build_query($eventQuery) ?>" class="telemetry-event-card <?= $selectedEventId === (int) ($event['id'] ?? 0) ? 'is-active' : '' ?>" data-telemetry-nav>
                                        <div class="telemetry-event-top">
                                            <span class="telemetry-pill"><code><?= esc((string) ($event['event_type'] ?? 'onbekend')) ?></code></span>
                                            <span class="<?= $severityClass ?>"><?= esc((string) ($event['severity'] ?? 'info')) ?></span>
                                        </div>
                                        <div class="telemetry-muted"><?= ! empty($event['created_at']) ? esc(date('d-m-Y H:i:s', strtotime((string) ($event['created_at'])))) : '—' ?></div>
                                        <div class="telemetry-event-meta">
                                            <span class="telemetry-pill"><i class="bi bi-broadcast"></i><?= esc((string) ($event['channel_name'] ?: 'Geen kanaal')) ?></span>
                                            <span class="telemetry-pill"><i class="bi bi-skip-forward-circle"></i><?= esc((string) ($event['last_action'] ?: 'Geen actie')) ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>

                    <div class="telemetry-drawer-column">
                        <?php if (empty($selectedEvent)): ?>
                            <div class="telemetry-empty">Selecteer een event om de payload en metadata te bekijken.</div>
                        <?php else: ?>
                            <?php $decoded = json_decode((string) ($selectedEvent['data_json'] ?? '{}'), true) ?: []; ?>
                            <section class="telemetry-drawer-section">
                                <div class="telemetry-drawer-section-head">
                                    <h4>Payload detail</h4>
                                    <span class="telemetry-pill"><code><?= esc((string) ($selectedEvent['event_type'] ?? 'onbekend')) ?></code></span>
                                </div>
                                <div style="padding: 1rem; display: grid; gap: .9rem;">
                                    <dl class="row small mb-0">
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

                                    <form method="post" action="<?= base_url('admin/telemetry/delete') ?>" onsubmit="return confirm('Dit telemetry event verwijderen?');" class="d-inline-flex gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= esc((string) ($selectedEvent['id'] ?? 0)) ?>">
                                        <input type="hidden" name="q" value="<?= esc($query) ?>">
                                        <input type="hidden" name="type" value="<?= esc($type) ?>">
                                        <input type="hidden" name="severity" value="<?= esc($severity) ?>">
                                        <input type="hidden" name="app_version" value="<?= esc($appVersion) ?>">
                                        <input type="hidden" name="group_query" value="<?= esc($groupQuery) ?>">
                                        <input type="hidden" name="sort" value="<?= esc($groupSort) ?>">
                                        <input type="hidden" name="per_page" value="<?= esc((string) $perPage) ?>">
                                        <input type="hidden" name="fingerprint" value="<?= esc($selectedFingerprint) ?>">
                                        <input type="hidden" name="page" value="<?= esc((string) $page) ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash3 me-1"></i>Verwijder event
                                        </button>
                                    </form>

                                    <div>
                                        <label class="form-label small text-muted">Gesaniteerde payload</label>
                                        <pre class="telemetry-payload-box"><?= esc(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                                    </div>
                                </div>
                            </section>

                            <section class="telemetry-drawer-section">
                                <div class="telemetry-drawer-section-head">
                                    <h4>Top event types 24 uur</h4>
                                </div>
                                <div style="padding: .95rem 1rem; display: grid; gap: .5rem;">
                                    <?php if (empty($overview['topTypes24h'])): ?>
                                        <div class="telemetry-muted">Nog geen data beschikbaar.</div>
                                    <?php else: ?>
                                        <?php foreach ($overview['topTypes24h'] as $typeRow): ?>
                                            <div class="d-flex justify-content-between align-items-center gap-2">
                                                <span class="telemetry-pill"><code><?= esc((string) ($typeRow['event_type'] ?? 'onbekend')) ?></code></span>
                                                <span class="telemetry-muted"><?= number_format((int) ($typeRow['total'] ?? 0)) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </section>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (() => {
        let isNavigating = false;

        const getPageRoot = () => document.getElementById('telemetry-page');
        const getDrawerShell = (root = document) => root.querySelector('[data-telemetry-drawer-shell]');

        const revealDrawer = (root = document) => {
            const shell = getDrawerShell(root);
            if (!shell || shell.dataset.open !== '1') {
                return;
            }

            requestAnimationFrame(() => {
                shell.classList.add('is-visible');
            });
        };

        const fetchAndSwap = async (url, pushState = true) => {
            if (isNavigating) {
                return;
            }

            const currentRoot = getPageRoot();
            if (!currentRoot) {
                window.location.assign(url);
                return;
            }

            isNavigating = true;
            currentRoot.classList.add('is-loading');

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Telemetry request failed with status ${response.status}`);
                }

                const html = await response.text();
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                const nextRoot = parsed.getElementById('telemetry-page');
                if (!nextRoot) {
                    throw new Error('Telemetry root not found in response');
                }

                currentRoot.replaceWith(nextRoot);
                revealDrawer(nextRoot);

                if (pushState) {
                    window.history.pushState({ telemetryUrl: url }, '', url);
                }
            } catch (error) {
                console.debug('Telemetry async navigation failed, falling back to full reload.', error);
                window.location.assign(url);
            } finally {
                const root = getPageRoot();
                if (root) {
                    root.classList.remove('is-loading');
                }
                isNavigating = false;
            }
        };

        const handleRowClick = (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return false;
            }

            const row = target.closest('[data-telemetry-row]');
            if (!row) {
                return false;
            }

            if (target.closest('a, button, input, select, textarea, label, form')) {
                return false;
            }

            const url = row.getAttribute('data-url');
            if (!url) {
                return false;
            }

            event.preventDefault();
            fetchAndSwap(url);
            return true;
        };

        document.addEventListener('click', (event) => {
            if (handleRowClick(event)) {
                return;
            }

            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const link = target.closest('a[data-telemetry-nav]');
            if (!(link instanceof HTMLAnchorElement)) {
                return;
            }

            const url = new URL(link.href, window.location.origin);
            if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) {
                return;
            }

            if (link.hasAttribute('data-telemetry-close')) {
                const shell = getDrawerShell();
                if (shell) {
                    event.preventDefault();
                    shell.classList.remove('is-visible');
                    window.setTimeout(() => fetchAndSwap(link.href), 170);
                    return;
                }
            }

            event.preventDefault();
            fetchAndSwap(link.href);
        });

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.matches('[data-telemetry-form]')) {
                return;
            }

            event.preventDefault();
            const action = form.getAttribute('action') || window.location.href;
            const url = new URL(action, window.location.origin);
            const params = new URLSearchParams(new FormData(form));
            url.search = params.toString();
            fetchAndSwap(url.toString());
        });

        window.addEventListener('popstate', () => {
            if (!window.location.pathname.includes('/admin/telemetry')) {
                return;
            }

            fetchAndSwap(window.location.href, false);
        });

        revealDrawer();
    })();
</script>
<?= $this->endSection() ?>
