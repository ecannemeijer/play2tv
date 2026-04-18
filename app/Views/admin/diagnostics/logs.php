<?= $this->extend('admin/layout') ?>

<?= $this->section('head') ?>
<style>
    .log-shell {
        display: grid;
        grid-template-columns: 340px minmax(0, 1fr);
        gap: 1.25rem;
    }
    .log-file-list {
        max-height: calc(100vh - 220px);
        overflow: auto;
    }
    .log-file-link {
        display: block;
        padding: .85rem 1rem;
        border-bottom: 1px solid #1e2035;
        color: inherit;
        text-decoration: none;
        transition: background .15s ease, border-color .15s ease;
    }
    .log-file-link:hover,
    .log-file-link.active {
        background: rgba(124, 58, 237, .16);
        border-color: rgba(196, 181, 253, .25);
    }
    .log-meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .75rem;
    }
    .log-meta-card {
        background: #121528;
        border: 1px solid #242945;
        border-radius: 12px;
        padding: .85rem 1rem;
    }
    .log-meta-label {
        color: #94a3b8;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .08em;
    }
    .log-meta-value {
        color: #f8fafc;
        font-weight: 600;
        margin-top: .3rem;
        word-break: break-word;
    }
    .log-viewer {
        background: linear-gradient(180deg, #0f172a, #111827);
        border: 1px solid #27324b;
        border-radius: 16px;
        overflow: hidden;
    }
    .log-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #27324b;
        background: rgba(15, 23, 42, .85);
    }
    .log-lines {
        max-height: calc(100vh - 350px);
        overflow: auto;
        padding: 1.25rem;
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
        gap: .9rem;
    }
    .log-entry-card {
        border: 1px solid #29344d;
        border-left-width: 5px;
        border-radius: 16px;
        padding: 1rem 1.1rem;
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
        margin-bottom: .75rem;
    }
    .log-entry-title { min-width: 0; }
    .log-entry-event {
        font-size: 1rem;
        font-weight: 800;
        color: #f8fafc;
        letter-spacing: .01em;
    }
    .log-entry-detail {
        margin-top: .3rem;
        color: #d5dfeb;
        font-size: .96rem;
        line-height: 1.45;
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
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: .7rem;
    }
    .log-chip {
        background: rgba(30, 41, 59, .72);
        border: 1px solid #2a3955;
        border-radius: 12px;
        padding: .7rem .8rem;
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
        font-size: .88rem;
        line-height: 1.45;
        word-break: break-word;
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
        .log-shell { grid-template-columns: 1fr; }
        .log-file-list, .log-lines { max-height: none; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
    <form method="get" class="d-flex gap-2 align-items-center flex-wrap">
        <input type="text" name="q" class="form-control" value="<?= esc($query) ?>" placeholder="Zoek op device of bestandsnaam" style="min-width:280px;">
        <?php if ($selectedFile !== ''): ?>
            <input type="hidden" name="file" value="<?= esc($selectedFile) ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-search me-1"></i>Filter
        </button>
    </form>
    <span class="badge bg-secondary"><?= number_format(count($logFiles)) ?> logs</span>
</div>

<div class="log-shell">
    <div class="card">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-folder2-open me-2"></i>Beschikbare logs</h6>
            <small class="text-muted">writable/uploads/logs</small>
        </div>
        <div class="log-file-list">
            <?php if ($logFiles === []): ?>
                <div class="log-empty-state">
                    <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                    Geen logbestanden gevonden.
                </div>
            <?php else: ?>
                <?php foreach ($logFiles as $file): ?>
                    <?php $isActive = $selectedFile === $file['name']; ?>
                    <a
                        href="<?= base_url('admin/diagnostics/logs?' . http_build_query(array_filter(['file' => $file['name'], 'q' => $query]))) ?>"
                        class="log-file-link <?= $isActive ? 'active' : '' ?>"
                    >
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold"><?= esc($file['name']) ?></div>
                                <div class="small text-muted mt-1">
                                    Device: <?= esc($file['device_id'] !== '' ? $file['device_id'] : 'onbekend') ?>
                                </div>
                            </div>
                            <span class="badge text-bg-dark"><?= esc(date('d-m H:i', $file['modified_at'])) ?></span>
                        </div>
                        <div class="small text-muted mt-2">
                            <?= esc(number_format($file['size'])) ?> bytes
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex flex-column gap-3">
        <?php if ($activeLog === null): ?>
            <div class="log-viewer">
                <div class="log-empty-state">
                    <i class="bi bi-journal-text fs-1 d-block mb-2"></i>
                    Kies links een logbestand om de inhoud te bekijken.
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
                        <div class="small text-muted">Eventweergave met compacte blokken voor timestamp, detail, bron, resolved URL en netwerkstatus.</div>
                    </div>
                    <div class="d-flex gap-2">
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
                            Geen gestructureerde events gevonden in dit bestand.
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
</div>

<?= $this->endSection() ?>