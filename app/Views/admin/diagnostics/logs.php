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
        font-family: Consolas, 'Cascadia Code', monospace;
        font-size: .9rem;
        line-height: 1.45;
    }
    .log-line {
        display: grid;
        grid-template-columns: 64px minmax(0, 1fr);
        gap: 1rem;
        padding: .15rem 1.25rem;
        border-left: 3px solid transparent;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .log-line:hover { background: rgba(148, 163, 184, .06); }
    .log-line-number { color: #64748b; text-align: right; user-select: none; }
    .log-line-content { color: #dbe4f0; }
    .log-line-header { border-left-color: #60a5fa; background: rgba(37, 99, 235, .08); }
    .log-line-debug { border-left-color: #f59e0b; background: rgba(245, 158, 11, .07); }
    .log-line-empty { border-left-color: transparent; }
    .log-line-warning { border-left-color: #f59e0b; background: rgba(245, 158, 11, .09); }
    .log-line-error { border-left-color: #ef4444; background: rgba(239, 68, 68, .10); }
    .log-line-success { border-left-color: #10b981; background: rgba(16, 185, 129, .08); }
    .log-line-accent { border-left-color: #a78bfa; background: rgba(167, 139, 250, .08); }
    .log-timestamp { color: #93c5fd; }
    .log-event { color: #f8fafc; font-weight: 700; }
    .log-detail { color: #cbd5e1; }
    .log-key { color: #f9a8d4; }
    .log-value { color: #e5e7eb; }
    .log-source-label,
    .log-network-label { color: #94a3b8; }
    .log-source-value { color: #67e8f9; }
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
                        <div class="small text-muted">Kleurcodering: errors rood, waarschuwingen geel, success groen, debug amber.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('admin/diagnostics/logs/download?' . http_build_query(['file' => $activeLog['name']])) ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-download me-1"></i>Download
                        </a>
                    </div>
                </div>
                <div class="log-lines">
                    <?php foreach ($contentLines as $index => $line): ?>
                        <?php
                            $trimmed = trim($line);
                            $lineClass = 'log-line-accent';
                            if ($trimmed === '') {
                                $lineClass = 'log-line-empty';
                            } elseif (preg_match('/^(VelixaTV support log bundle|Generated:|App version:|Android:|Device:|Device ID:|Current channel:|Current channel id:|Audio delay ms:|Latest summary:)/', $trimmed) === 1) {
                                $lineClass = 'log-line-header';
                            } elseif (preg_match('/^Debug /', $trimmed) === 1) {
                                $lineClass = 'log-line-debug';
                            } elseif (preg_match('/\b(error|failed|exception|fatal|invalid|rejected)\b/i', $trimmed) === 1) {
                                $lineClass = 'log-line-error';
                            } elseif (preg_match('/\b(warning|buffering|stalled|retry|skipped)\b/i', $trimmed) === 1) {
                                $lineClass = 'log-line-warning';
                            } elseif (preg_match('/\b(ready|success|uploaded|complete|first_frame|play)\b/i', $trimmed) === 1) {
                                $lineClass = 'log-line-success';
                            }
                        ?>
                        <div class="log-line <?= $lineClass ?>">
                            <div class="log-line-number"><?= $index + 1 ?></div>
                            <div class="log-line-content">
                                <?php if (preg_match('/^\[(\d+)\]\s+([^:]+)\s+::\s+(.*)$/', $line, $matches) === 1): ?>
                                    <span class="log-timestamp">[<?= esc($matches[1]) ?>]</span>
                                    <span class="log-event ms-1"><?= esc($matches[2]) ?></span>
                                    <span class="log-detail ms-1">:: <?= esc($matches[3]) ?></span>
                                <?php elseif (preg_match('/^\s{2}(source|resolved|network)=(.*)$/', $line, $matches) === 1): ?>
                                    <span class="log-source-label"><?= esc($matches[1]) ?>=</span><span class="log-source-value"><?= esc($matches[2]) ?></span>
                                <?php elseif (preg_match('/^([^:]+):(.*)$/', $line, $matches) === 1): ?>
                                    <span class="log-key"><?= esc(trim($matches[1])) ?>:</span>
                                    <span class="log-value ms-1"><?= esc(trim($matches[2])) ?></span>
                                <?php else: ?>
                                    <?= esc($line) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($truncated): ?>
                        <div class="log-line log-line-warning">
                            <div class="log-line-number">…</div>
                            <div class="log-line-content">Weergave ingekort na <?= esc((string) count($contentLines)) ?> regels. Download het bestand voor de volledige inhoud.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>