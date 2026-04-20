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

        <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
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
                <button type="submit" class="btn btn-outline-warning btn-sm" <?= $baseQuery === [] ? 'disabled' : '' ?>>
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

        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="<?= base_url('admin/telemetry?severity=error') ?>" class="btn btn-sm btn-outline-danger">Alle errors</a>
            <a href="<?= base_url('admin/telemetry?type=manual_report') ?>" class="btn btn-sm btn-outline-secondary">Manual reports</a>
            <a href="<?= base_url('admin/telemetry?type=player_rebuffer') ?>" class="btn btn-sm btn-outline-secondary">Rebuffers</a>
            <a href="<?= base_url('admin/telemetry?type=crash') ?>" class="btn btn-sm btn-outline-secondary">Crashes</a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-activity me-2"></i>Telemetry events</h6>
                <span class="badge bg-secondary"><?= number_format($totalEvents) ?> totaal</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($events)): ?>
                    <p class="text-muted p-3 mb-0">Geen telemetry events gevonden voor de huidige filters.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tijd</th>
                                    <th>Type</th>
                                    <th>Severity</th>
                                    <th>Kanaal</th>
                                    <th>App</th>
                                    <th>Actie</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <?php
                                        $rowQuery = array_merge($baseQuery, ['page' => $page, 'id' => $event['id']]);
                                        $severityClass = match ($event['severity'] ?? '') {
                                            'error' => 'bg-danger',
                                            'warning' => 'bg-warning text-dark',
                                            default => 'bg-secondary',
                                        };
                                    ?>
                                    <tr class="<?= ($selectedEvent['id'] ?? 0) === $event['id'] ? 'table-secondary' : '' ?>">
                                        <td>
                                            <a class="text-decoration-none" href="<?= current_url() . '?' . http_build_query($rowQuery) ?>">
                                                <small><?= $event['created_at'] ? date('d-m-Y H:i:s', strtotime((string) $event['created_at'])) : '—' ?></small>
                                            </a>
                                        </td>
                                        <td><code><?= esc((string) $event['event_type']) ?></code></td>
                                        <td><span class="badge <?= $severityClass ?>"><?= esc((string) $event['severity']) ?></span></td>
                                        <td><?= esc((string) ($event['channel_name'] ?: '—')) ?></td>
                                        <td>
                                            <div><?= esc((string) ($event['app_version'] ?: '—')) ?></div>
                                            <small class="text-muted">code <?= esc((string) ($event['app_code'] ?? '—')) ?></small>
                                        </td>
                                        <td><?= esc((string) ($event['last_action'] ?: '—')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3" aria-label="Telemetry pagination">
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
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="bi bi-file-earmark-code me-2"></i>Event detail</h6>
            </div>
            <div class="card-body">
                <?php if (empty($selectedEvent)): ?>
                    <p class="text-muted mb-0">Selecteer een event in de tabel om de volledige payload te bekijken.</p>
                <?php else: ?>
                    <?php $decoded = json_decode((string) ($selectedEvent['data_json'] ?? '{}'), true) ?: []; ?>
                    <dl class="row small mb-3">
                        <dt class="col-sm-5 text-muted">Type</dt>
                        <dd class="col-sm-7"><code><?= esc((string) $selectedEvent['event_type']) ?></code></dd>

                        <dt class="col-sm-5 text-muted">Ontvangen</dt>
                        <dd class="col-sm-7"><?= $selectedEvent['created_at'] ? date('d-m-Y H:i:s', strtotime((string) $selectedEvent['created_at'])) : '—' ?></dd>

                        <dt class="col-sm-5 text-muted">Client time</dt>
                        <dd class="col-sm-7"><?= $selectedEvent['client_timestamp'] ? date('d-m-Y H:i:s', strtotime((string) $selectedEvent['client_timestamp'])) : '—' ?></dd>

                        <dt class="col-sm-5 text-muted">Device</dt>
                        <dd class="col-sm-7"><?= esc((string) ($selectedEvent['device_name'] ?: '—')) ?></dd>

                        <dt class="col-sm-5 text-muted">Android</dt>
                        <dd class="col-sm-7"><?= esc((string) ($selectedEvent['android_version'] ?: '—')) ?></dd>

                        <dt class="col-sm-5 text-muted">Kanaal</dt>
                        <dd class="col-sm-7"><?= esc((string) ($selectedEvent['channel_name'] ?: '—')) ?></dd>

                        <dt class="col-sm-5 text-muted">Stream type</dt>
                        <dd class="col-sm-7"><?= esc((string) ($selectedEvent['stream_type'] ?: '—')) ?></dd>

                        <dt class="col-sm-5 text-muted">Fingerprint</dt>
                        <dd class="col-sm-7"><small><?= esc(substr((string) ($selectedEvent['fingerprint_hash'] ?? ''), 0, 16)) ?><?= ! empty($selectedEvent['fingerprint_hash']) ? '…' : '—' ?></small></dd>
                    </dl>

                    <form method="post" action="<?= base_url('admin/telemetry/delete') ?>" onsubmit="return confirm('Dit telemetry event verwijderen?');" class="mb-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= esc((string) $selectedEvent['id']) ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash3 me-1"></i>Verwijder dit event
                        </button>
                    </form>

                    <label class="form-label small text-muted">Gesaniteerde payload</label>
                    <pre class="mb-0 p-3 rounded" style="background:#0b1120;border:1px solid #1e293b;white-space:pre-wrap;word-break:break-word;"><?= esc(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Top event types 24 uur</h6>
            </div>
            <div class="card-body">
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

<?= $this->endSection() ?>