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