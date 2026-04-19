<?= $this->extend('admin/layout') ?>

<?= $this->section('head') ?>
<style>
    .log-primary-filters {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: .9rem;
    }
    .log-viewer-shell {
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }
    .log-meta-grid {
        display: grid;
        grid-auto-flow: column;
        grid-auto-columns: minmax(132px, 1fr);
        gap: .6rem;
        overflow-x: auto;
        padding-bottom: .15rem;
    }
    .log-meta-card {
        background: #121528;
        border: 1px solid #242945;
        border-radius: 12px;
        padding: .65rem .8rem;
        min-height: 78px;
    }
    .log-meta-label {
        color: #94a3b8;
        font-size: .66rem;
        text-transform: uppercase;
        letter-spacing: .08em;
    }
    .log-meta-value {
        color: #f8fafc;
        font-weight: 600;
        margin-top: .22rem;
        font-size: .92rem;
        line-height: 1.25;
        word-break: break-word;
    }
    .log-viewer {
        background: linear-gradient(180deg, #0f172a, #111827);
        border: 1px solid #27324b;
        border-radius: 16px;
        overflow: hidden;
    }
    .log-modal .modal-content {
        background: #111827;
        border: 1px solid #27324b;
        color: #e2e8f0;
    }
    .log-modal .modal-header,
    .log-modal .modal-footer {
        border-color: #27324b;
    }
    .log-modal .btn-close {
        filter: invert(1) grayscale(1);
    }
    .log-modal-filter {
        display: flex;
        gap: .75rem;
        align-items: flex-end;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .log-modal-filter .form-control {
        min-width: 320px;
    }
    .log-modal-list {
        max-height: 70vh;
        overflow: auto;
        display: grid;
        gap: .65rem;
    }
    .log-modal-item {
        display: flex;
        justify-content: space-between;
        gap: .9rem;
        padding: .8rem .9rem;
        border: 1px solid #28344d;
        border-radius: 14px;
        background: rgba(15, 23, 42, .8);
    }
    .log-modal-item.active {
        border-color: rgba(167, 139, 250, .55);
        box-shadow: inset 0 0 0 1px rgba(167, 139, 250, .25);
    }
    .log-modal-main {
        min-width: 0;
        flex: 1;
    }
    .log-modal-title {
        display: flex;
        align-items: center;
        gap: .45rem;
        flex-wrap: wrap;
        margin-bottom: .4rem;
    }
    .log-file-name {
        font-weight: 700;
        color: #f8fafc;
        word-break: break-word;
    }
    .log-file-badges {
        display: flex;
        gap: .35rem;
        flex-wrap: wrap;
    }
    .log-file-badge {
        display: inline-flex;
        align-items: center;
        padding: .16rem .45rem;
        border-radius: 999px;
        background: rgba(51, 65, 85, .8);
        border: 1px solid #334155;
        color: #cbd5e1;
        font-size: .72rem;
        line-height: 1.1;
    }
    .log-file-badge-accent {
        background: rgba(76, 29, 149, .28);
        border-color: rgba(167, 139, 250, .35);
        color: #ddd6fe;
    }
    .log-file-meta {
        display: flex;
        gap: .8rem;
        flex-wrap: wrap;
        font-size: .8rem;
        color: #94a3b8;
    }
    .log-modal-actions {
        display: flex;
        gap: .45rem;
        align-items: flex-start;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .log-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: .9rem 1.1rem;
        border-bottom: 1px solid #27324b;
        background: rgba(15, 23, 42, .85);
    }
    .log-secondary-filters {
        display: flex;
        gap: .65rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .log-filter-field {
        min-width: 160px;
    }
    .log-lines {
        max-height: calc(100vh - 245px);
        overflow: auto;
        padding: 1rem;
        background: radial-gradient(circle at top, rgba(30, 41, 59, .6), rgba(15, 23, 42, .98));
    }
    .log-header-block {
        background: rgba(15, 23, 42, .82);
        border: 1px solid #24314a;
        border-radius: 14px;
        padding: 1rem 1.1rem;
        margin-bottom: 1rem;
    }
    .log-header-line {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        padding: .2rem 0;
        font-family: Consolas, 'Cascadia Code', monospace;
        font-size: .9rem;
    }
    .log-header-key { color: #f9a8d4; font-weight: 700; }
    .log-header-value { color: #e2e8f0; }
    .log-entry-list {
        display: grid;
        gap: .75rem;
    }
    .log-entry-card {
        border: 1px solid #29344d;
        border-left-width: 5px;
        border-radius: 14px;
        padding: .85rem .95rem;
        background: rgba(15, 23, 42, .84);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.02);
    }
    .log-entry-neutral { border-left-color: #818cf8; }
    .log-entry-warning { border-left-color: #f59e0b; background: rgba(120, 53, 15, .14); }
    .log-entry-error { border-left-color: #ef4444; background: rgba(127, 29, 29, .16); }
    .log-entry-success { border-left-color: #10b981; background: rgba(6, 78, 59, .16); }
    .log-entry-debug { border-left-color: #fbbf24; background: rgba(120, 53, 15, .11); }
    .log-entry-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: .6rem;
    }
    .log-entry-title { min-width: 0; }
    .log-entry-event {
        font-size: .95rem;
        font-weight: 800;
        color: #f8fafc;
        letter-spacing: .01em;
    }
    .log-entry-detail {
        margin-top: .2rem;
        color: #d5dfeb;
        font-size: .89rem;
        line-height: 1.35;
    }
    .log-entry-time {
        font-family: Consolas, 'Cascadia Code', monospace;
        color: #93c5fd;
        background: rgba(37, 99, 235, .12);
        border: 1px solid rgba(96, 165, 250, .18);
        border-radius: 999px;
        padding: .25rem .6rem;
        white-space: nowrap;
    }
    .log-entry-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: .55rem;
    }
    .log-chip {
        background: rgba(30, 41, 59, .72);
        border: 1px solid #2a3955;
        border-radius: 10px;
        padding: .55rem .7rem;
        min-width: 0;
    }
    .log-chip-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
        margin-bottom: .3rem;
    }
    .log-chip-value {
        font-family: Consolas, 'Cascadia Code', monospace;
        color: #dbeafe;
        font-size: .82rem;
        line-height: 1.35;
        word-break: break-word;
    }
    .log-results-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: rgba(30, 41, 59, .72);
        border: 1px solid #314056;
        color: #dbeafe;
        border-radius: 999px;
        padding: .3rem .7rem;
        font-size: .8rem;
    }
    .log-footer-note {
        margin-top: 1rem;
        padding: .9rem 1rem;
        border-radius: 12px;
        background: rgba(71, 85, 105, .18);
        border: 1px solid #334155;
        color: #cbd5e1;
        font-family: Consolas, 'Cascadia Code', monospace;
        font-size: .88rem;
    }
    .log-empty-state {
        padding: 3rem 2rem;
        text-align: center;
        color: #94a3b8;
    }
    @media (max-width: 1200px) {
        .log-meta-grid { grid-auto-flow: row; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="log-primary-filters">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#logFilesModal">
            <i class="bi bi-folder2-open me-1"></i>Laad logbestand
        </button>
        <span class="badge bg-secondary"><?= number_format(count($logFiles)) ?> logs</span>
    </div>
</div>

<div class="log-viewer-shell">
    <?php if ($activeLog === null): ?>
        <div class="log-viewer">
            <div class="log-empty-state">
                <i class="bi bi-journal-text fs-1 d-block mb-2"></i>
                Kies via de knop <strong>Laad logbestand</strong> een log om de inhoud te bekijken.
            </div>
        </div>
    <?php else: ?>
        <div class="log-meta-grid">
            <?php foreach ($meta as $label => $value): ?>
                <div class="log-meta-card">
                    <div class="log-meta-label"><?= esc($label) ?></div>
                    <div class="log-meta-value"><?= esc($value) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="log-viewer">
                <div class="log-toolbar">
                    <div>
                        <div class="fw-semibold"><?= esc($activeLog['name']) ?></div>
                        <div class="small text-muted">Compacte eventweergave met filtering op warning/error/debug/success en zoekwoord.</div>
                    </div>
                    <div class="d-flex gap-2 align-items-center flex-wrap justify-content-end">
                        <form method="get" class="log-secondary-filters">
                            <input type="hidden" name="file" value="<?= esc($activeLog['name']) ?>">
                            <?php if ($query !== ''): ?>
                                <input type="hidden" name="q" value="<?= esc($query) ?>">
                            <?php endif; ?>
                            <div class="log-filter-field">
                                <label class="form-label small text-muted">Type</label>
                                <select name="severity" class="form-select form-select-sm">
                                    <option value="">Alles</option>
                                    <?php foreach (['warning' => 'Warnings', 'error' => 'Errors', 'success' => 'Success', 'debug' => 'Debug', 'neutral' => 'Overig'] as $value => $label): ?>
                                        <option value="<?= esc($value) ?>" <?= $severity === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="log-filter-field" style="min-width:220px;">
                                <label class="form-label small text-muted">Zoekwoord</label>
                                <input type="text" name="term" class="form-control form-control-sm" value="<?= esc($term) ?>" placeholder="Bijv. buffering, ignored, line.dino.ws">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-funnel me-1"></i>Toepassen
                            </button>
                        </form>
                        <span class="log-results-badge">
                            <i class="bi bi-filter-circle"></i><?= number_format(count($parsedEntries)) ?>/<?= number_format($totalParsedEntries) ?> events
                        </span>
                        <a href="<?= base_url('admin/diagnostics/logs/download?' . http_build_query(['file' => $activeLog['name']])) ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-download me-1"></i>Download
                        </a>
                    </div>
                </div>
                <div class="log-lines">
                    <?php if ($headerLines !== []): ?>
                        <div class="log-header-block">
                            <?php foreach ($headerLines as $line): ?>
                                <?php if (preg_match('/^([^:]+):(.*)$/', $line, $matches) === 1): ?>
                                    <div class="log-header-line">
                                        <span class="log-header-key"><?= esc(trim($matches[1])) ?>:</span>
                                        <span class="log-header-value"><?= esc(trim($matches[2])) ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="log-header-line"><span class="log-header-value"><?= esc($line) ?></span></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($parsedEntries !== []): ?>
                        <div class="log-entry-list">
                            <?php foreach ($parsedEntries as $entry): ?>
                                <div class="log-entry-card log-entry-<?= esc($entry['tone']) ?>">
                                    <div class="log-entry-top">
                                        <div class="log-entry-title">
                                            <div class="log-entry-event"><?= esc($entry['event']) ?></div>
                                            <div class="log-entry-detail"><?= esc($entry['detail']) ?></div>
                                        </div>
                                        <div class="log-entry-time">[<?= esc($entry['timestamp']) ?>]</div>
                                    </div>
                                    <div class="log-entry-grid">
                                        <div class="log-chip">
                                            <div class="log-chip-label">Source</div>
                                            <div class="log-chip-value"><?= esc($entry['source']) ?></div>
                                        </div>
                                        <div class="log-chip">
                                            <div class="log-chip-label">Resolved</div>
                                            <div class="log-chip-value"><?= esc($entry['resolved']) ?></div>
                                        </div>
                                        <div class="log-chip">
                                            <div class="log-chip-label">Network</div>
                                            <div class="log-chip-value"><?= esc($entry['network']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="log-empty-state">
                            Geen events gevonden voor de huidige filters.
                        </div>
                    <?php endif; ?>

                    <?php foreach ($footerLines as $footerLine): ?>
                        <div class="log-footer-note"><?= esc($footerLine) ?></div>
                    <?php endforeach; ?>

                    <?php if ($truncated): ?>
                        <div class="log-footer-note">
                            Weergave ingekort na <?= esc((string) count($contentLines)) ?> regels. Download het bestand voor de volledige inhoud.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
    <?php endif; ?>
</div>

<div class="modal fade log-modal" id="logFilesModal" tabindex="-1" aria-labelledby="logFilesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="logFilesModalLabel">Beschikbare logbestanden</h5>
                    <div class="small text-muted">Kies een log om te laden, of verwijder losse logs of alles tegelijk.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Sluiten"></button>
            </div>
            <div class="modal-body">
                <form method="get" class="log-modal-filter">
                    <?php if ($selectedFile !== ''): ?>
                        <input type="hidden" name="file" value="<?= esc($selectedFile) ?>">
                    <?php endif; ?>
                    <?php if ($severity !== ''): ?>
                        <input type="hidden" name="severity" value="<?= esc($severity) ?>">
                    <?php endif; ?>
                    <?php if ($term !== ''): ?>
                        <input type="hidden" name="term" value="<?= esc($term) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="openPicker" value="1">
                    <div>
                        <label class="form-label small text-muted">Bestanden filteren</label>
                        <input type="text" name="q" class="form-control" value="<?= esc($query) ?>" placeholder="Zoek op device of bestandsnaam">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                </form>

                <?php if ($logFiles === []): ?>
                    <div class="log-empty-state py-4">
                        <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                        Geen logbestanden gevonden.
                    </div>
                <?php else: ?>
                    <div class="log-modal-list">
                        <?php foreach ($logFiles as $file): ?>
                            <?php $isActive = $selectedFile === $file['name']; ?>
                            <div class="log-modal-item <?= $isActive ? 'active' : '' ?>">
                                <div class="log-modal-main">
                                    <div class="log-modal-title">
                                        <span class="log-file-name"><?= esc($file['name']) ?></span>
                                        <div class="log-file-badges">
                                            <?php if ($file['device_id'] !== ''): ?>
                                                <span class="log-file-badge log-file-badge-accent"><?= esc($file['device_id']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($file['entry_count'] !== ''): ?>
                                                <span class="log-file-badge"><?= esc($file['entry_count']) ?> events</span>
                                            <?php endif; ?>
                                            <span class="log-file-badge"><?= esc(number_format($file['size'])) ?> B</span>
                                        </div>
                                    </div>
                                    <div class="log-file-meta">
                                        <span>Gewijzigd: <?= esc(date('d-m-Y H:i:s', $file['modified_at'])) ?></span>
                                        <?php if ($file['generated_at'] !== ''): ?>
                                            <span>Generated: <?= esc($file['generated_at']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($file['app_version'] !== ''): ?>
                                            <span>App: <?= esc($file['app_version']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="log-modal-actions">
                                    <a href="<?= base_url('admin/diagnostics/logs?' . http_build_query(array_filter(['file' => $file['name'], 'q' => $query, 'severity' => $severity, 'term' => $term]))) ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-box-arrow-in-down-right me-1"></i>Laden
                                    </a>
                                    <form method="post" action="<?= base_url('admin/diagnostics/logs/delete') ?>" onsubmit="return confirm('Weet je zeker dat je dit logbestand wilt verwijderen?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="file" value="<?= esc($file['name']) ?>">
                                        <input type="hidden" name="q" value="<?= esc($query) ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash3 me-1"></i>Verwijder
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <small class="text-muted">Opslaglocatie: writable/uploads/logs</small>
                <div class="d-flex gap-2">
                    <form method="post" action="<?= base_url('admin/diagnostics/logs/delete-all') ?>" onsubmit="return confirm('Weet je zeker dat je alle logbestanden wilt verwijderen?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="q" value="<?= esc($query) ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm" <?= $logFiles === [] ? 'disabled' : '' ?>>
                            <i class="bi bi-trash me-1"></i>Verwijder alles
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Sluiten</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<?php if ($openPicker): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('logFilesModal');
    if (!modalElement) {
        return;
    }

    const modal = new bootstrap.Modal(modalElement);
    modal.show();
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->endSection() ?>