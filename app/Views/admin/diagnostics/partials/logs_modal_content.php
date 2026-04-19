<div class="modal-header">
    <div>
        <h5 class="modal-title mb-1" id="logFilesModalLabel">Beschikbare logbestanden</h5>
        <div class="small text-muted">Kies een log om te laden, of verwijder losse logs of alles tegelijk.</div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Sluiten"></button>
</div>
<div class="modal-body">
    <form method="get" class="log-modal-filter" id="logModalFilterForm">
        <?php if ($selectedFile !== ''): ?>
            <input type="hidden" name="file" value="<?= esc($selectedFile) ?>">
        <?php endif; ?>
        <?php if ($severity !== ''): ?>
            <input type="hidden" name="severity" value="<?= esc($severity) ?>">
        <?php endif; ?>
        <?php if ($term !== ''): ?>
            <input type="hidden" name="term" value="<?= esc($term) ?>">
        <?php endif; ?>
        <div>
            <label class="form-label small text-muted">Bestanden filteren</label>
            <input type="text" name="q" class="form-control" value="<?= esc($query) ?>" placeholder="Zoek op device of bestandsnaam" autocomplete="off">
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
                        <button type="button" class="btn btn-primary btn-sm js-load-log" data-file-name="<?= esc($file['name']) ?>">
                            <i class="bi bi-box-arrow-in-down-right me-1"></i>Laden
                        </button>
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