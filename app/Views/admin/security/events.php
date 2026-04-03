<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 gap-3 flex-wrap">
    <div>
        <a href="<?= base_url('admin/security/events') ?>" class="btn btn-outline-secondary btn-sm <?= $suspiciousOnly ? '' : 'active' ?>">
            <i class="bi bi-list-ul me-1"></i>Alle events
        </a>
        <a href="<?= base_url('admin/security/suspicious') ?>" class="btn btn-outline-danger btn-sm ms-2 <?= $suspiciousOnly ? 'active' : '' ?>">
            <i class="bi bi-exclamation-triangle me-1"></i>Verdachte activiteit
        </a>
    </div>

    <form method="get" class="row g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label small text-muted">Zoeken</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= esc($query) ?>" placeholder="event, route of email">
        </div>
        <div class="col-auto">
            <label class="form-label small text-muted">Severity</label>
            <select name="severity" class="form-select form-select-sm">
                <option value="">Alle</option>
                <?php foreach (['warning', 'error', 'critical', 'alert'] as $option): ?>
                    <option value="<?= esc($option) ?>" <?= $severity === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small text-muted">User ID</label>
            <input type="number" name="user_id" class="form-control form-control-sm" value="<?= esc((string) $userId) ?>" min="1">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search me-1"></i>Filter
            </button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header py-3">
        <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i><?= $suspiciousOnly ? 'Verdachte activiteit' : 'Security events' ?></h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($events)): ?>
            <p class="text-muted p-3 mb-0">Geen events gevonden voor de huidige filters.</p>
        <?php else: ?>
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tijd</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Severity</th>
                        <th>Route</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <?php $details = json_decode((string) ($event['details'] ?? '{}'), true) ?: []; ?>
                        <tr>
                            <td><small><?= $event['created_at'] ? date('d-m-Y H:i:s', strtotime($event['created_at'])) : '—' ?></small></td>
                            <td>
                                <?php if (! empty($event['user_id'])): ?>
                                    <a href="<?= base_url('admin/users/' . $event['user_id']) ?>">#<?= esc((string) $event['user_id']) ?></a><br>
                                    <small class="text-muted"><?= esc($event['email'] ?? 'onbekend') ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Gast / onbekend</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= esc($event['event_type']) ?></code></td>
                            <td>
                                <span class="badge <?= ($event['severity'] ?? '') === 'critical' ? 'bg-danger' : (($event['severity'] ?? '') === 'error' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                    <?= esc($event['severity']) ?>
                                </span>
                            </td>
                            <td><small><?= esc($event['route'] ?: '—') ?></small></td>
                            <td>
                                <div class="small text-muted">
                                    <?php foreach (array_slice($details, 0, 4, true) as $key => $value): ?>
                                        <div><strong><?= esc((string) $key) ?>:</strong> <?= esc(is_scalar($value) ? (string) $value : json_encode($value)) ?></div>
                                    <?php endforeach; ?>
                                    <?php if (empty($details)): ?>—<?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>