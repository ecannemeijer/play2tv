<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Terug naar lijst
    </a>
    <a href="<?= base_url('admin/users/' . $user['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm ms-2">
        <i class="bi bi-pencil me-1"></i>Bewerken
    </a>
    <form method="post" action="<?= base_url('admin/users/' . $user['id'] . '/force-logout') ?>" class="d-inline ms-2" onsubmit="return confirm('Alle sessies en tokens van deze gebruiker intrekken?');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-right me-1"></i>Force logout
        </button>
    </form>
</div>

<div class="row g-3">

    <!-- User Info -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Gebruikersinfo</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">ID</dt>
                    <dd class="col-7">#<?= $user['id'] ?></dd>

                    <dt class="col-5 text-muted">E-mail</dt>
                    <dd class="col-7"><?= esc($user['email']) ?></dd>

                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        <?= $user['is_active']
                            ? '<span class="badge bg-success">Actief</span>'
                            : '<span class="badge bg-danger">Geblokkeerd</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">Premium</dt>
                    <dd class="col-7">
                        <?php if ($user['premium']): ?>
                            <span class="badge badge-premium"><i class="bi bi-star-fill me-1"></i>Premium</span>
                            <?php if ($user['premium_until']): ?>
                                <br><small class="text-muted">t/m <?= date('d-m-Y', strtotime($user['premium_until'])) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary">Gratis</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">Punten</dt>
                    <dd class="col-7"><strong class="text-warning"><?= number_format($totalPoints) ?></strong></dd>

                    <dt class="col-5 text-muted">Geregistreerd</dt>
                    <dd class="col-7 small"><?= $user['created_at'] ? date('d-m-Y H:i', strtotime($user['created_at'])) : '—' ?></dd>

                    <dt class="col-5 text-muted">Laatste login</dt>
                    <dd class="col-7 small"><?= $user['last_login_at'] ? date('d-m-Y H:i', strtotime($user['last_login_at'])) : '—' ?></dd>

                    <dt class="col-5 text-muted">Login IP</dt>
                    <dd class="col-7"><code><?= esc($user['last_login_ip'] ?? '—') ?></code></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Add Points -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="bi bi-coin me-2"></i>Punten beheren</h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('admin/users/' . $user['id'] . '/points') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Punten (+/-)</label>
                        <input type="number" name="points" class="form-control" placeholder="100 of -50" required>
                        <div class="form-text text-muted">Positief = toevoegen, negatief = aftrekken</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reden</label>
                        <input type="text" name="reason" class="form-control" placeholder="Admin handmatig" value="Admin handmatig">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle me-1"></i>Verwerk punten
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Devices -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="bi bi-phone me-2"></i>Apparaten (<?= count($devices) ?>)</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($devices)): ?>
                    <p class="text-muted p-3 mb-0">Geen apparaten.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Naam</th><th>Device ID</th><th>IP</th><th>Laatst gebruikt</th><th class="text-end">Acties</th></tr></thead>
                        <tbody>
                            <?php foreach ($devices as $device): ?>
                            <tr>
                                <td>
                                    <form method="post" action="<?= base_url('admin/users/' . $user['id'] . '/devices/' . $device['id'] . '/rename') ?>" class="d-flex gap-2 align-items-center">
                                        <?= csrf_field() ?>
                                        <input
                                            type="text"
                                            name="device_name"
                                            class="form-control form-control-sm"
                                            value="<?= esc($device['device_name'] ?? '') ?>"
                                            placeholder="Woonkamer"
                                            maxlength="100"
                                        >
                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </form>
                                </td>
                                <td><small><code><?= esc($device['device_id']) ?></code></small></td>
                                <td><small><?= esc($device['ip_address']) ?></small></td>
                                <td><small><?= $device['last_used'] ? date('d-m H:i', strtotime($device['last_used'])) : '—' ?></small></td>
                                <td class="text-end">
                                    <form method="post" action="<?= base_url('admin/users/' . $user['id'] . '/devices/' . $device['id'] . '/delete') ?>" onsubmit="return confirm('Weet je zeker dat je dit apparaat wilt verwijderen?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Actieve sessies / refresh tokens</h6>
                <span class="badge bg-secondary"><?= count($tokens) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tokens)): ?>
                    <p class="text-muted p-3 mb-0">Geen recente tokens gevonden.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Selector</th><th>Device</th><th>Laatst gebruikt</th><th>Status</th><th class="text-end">Actie</th></tr></thead>
                        <tbody>
                            <?php foreach ($tokens as $token): ?>
                            <tr>
                                <td><code><?= esc(substr((string) $token['selector'], 0, 12)) ?>...</code></td>
                                <td><small><?= esc($token['device_id'] ?: '—') ?></small></td>
                                <td><small><?= $token['last_used_at'] ? date('d-m-Y H:i', strtotime($token['last_used_at'])) : '—' ?></small></td>
                                <td>
                                    <?php if (! empty($token['revoked_at'])): ?>
                                        <span class="badge bg-danger">Revoked</span>
                                    <?php elseif (strtotime((string) $token['expires_at']) < time()): ?>
                                        <span class="badge bg-secondary">Expired</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (empty($token['revoked_at']) && strtotime((string) $token['expires_at']) >= time()): ?>
                                        <form method="post" action="<?= base_url('admin/users/' . $user['id'] . '/tokens/' . $token['id'] . '/revoke') ?>" onsubmit="return confirm('Deze token intrekken?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-slash-circle"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <small class="text-muted"><?= esc($token['revoked_reason'] ?? '—') ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-broadcast me-2"></i>Xtream diagnose</h6>
                <form method="post" action="<?= base_url('admin/users/' . $user['id'] . '/xtream-diagnostics') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-play-circle me-1"></i>Test verbinding
                    </button>
                </form>
            </div>
            <div class="card-body">
                <dl class="row small mb-3">
                    <dt class="col-4 text-muted">Server</dt>
                    <dd class="col-8"><?= esc($user['xtream_server'] ?? '—') ?></dd>
                    <dt class="col-4 text-muted">Username</dt>
                    <dd class="col-8"><?= esc($user['xtream_username'] ?? '—') ?></dd>
                </dl>

                <?php if (! empty($xtreamDiagnostics)): ?>
                    <div class="mb-2">
                        <span class="badge <?= ($xtreamDiagnostics['summary'] ?? '') === 'ok' ? 'bg-success' : (($xtreamDiagnostics['summary'] ?? '') === 'warning' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                            <?= esc(strtoupper((string) ($xtreamDiagnostics['summary'] ?? 'unknown'))) ?>
                        </span>
                        <small class="text-muted ms-2">Runtime: <?= esc((string) ($xtreamDiagnostics['duration_ms'] ?? 0)) ?> ms</small>
                    </div>
                    <?php foreach (($xtreamDiagnostics['checks'] ?? []) as $check): ?>
                        <div class="border rounded-3 p-2 mb-2" style="border-color:#2d2d44 !important; background:#141427;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong><?= esc($check['label'] ?? 'Check') ?></strong>
                                <span class="badge <?= ($check['status'] ?? '') === 'ok' ? 'bg-success' : (($check['status'] ?? '') === 'warning' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                    <?= esc(strtoupper((string) ($check['status'] ?? 'unknown'))) ?>
                                </span>
                            </div>
                            <div class="small"><?= esc($check['message'] ?? '—') ?></div>
                            <?php if (! empty($check['meta'])): ?>
                                <ul class="small text-muted mt-2 mb-0 ps-3">
                                    <?php foreach ($check['meta'] as $metaKey => $metaValue): ?>
                                        <li><?= esc((string) $metaKey) ?>: <?= esc((string) $metaValue) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Nog geen diagnose uitgevoerd voor deze gebruiker.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Watch History -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Kijkgeschiedenis (laatste 20)</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($history)): ?>
                    <p class="text-muted p-3 mb-0">Geen geschiedenis.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Type</th><th>ID</th><th>S/E</th><th>Voortgang</th><th>Gezien</th></tr></thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td><span class="badge <?= $h['content_type'] === 'movie' ? 'bg-info' : 'bg-success' ?>"><?= $h['content_type'] ?></span></td>
                                <td><code><?= esc($h['content_id']) ?></code></td>
                                <td><?= $h['season'] ? 'S' . $h['season'] . 'E' . $h['episode'] : '—' ?></td>
                                <td><small><?= gmdate('H:i:s', (int)$h['progress_seconds']) ?></small></td>
                                <td><small><?= $h['watched_at'] ? date('d-m H:i', strtotime($h['watched_at'])) : '—' ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- IP Log -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>IP log (laatste 20)</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ips)): ?>
                    <p class="text-muted p-3 mb-0">Geen IP logs.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>IP</th><th>User Agent</th><th>Tijdstip</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($ips, 0, 20) as $ip): ?>
                            <tr>
                                <td><code><?= esc($ip['ip_address']) ?></code></td>
                                <td><small class="text-muted" title="<?= esc($ip['user_agent']) ?>"><?= esc(substr($ip['user_agent'] ?? '', 0, 40)) ?>...</small></td>
                                <td><small><?= $ip['created_at'] ? date('d-m H:i', strtotime($ip['created_at'])) : '—' ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Points history -->
    <div class="col-12">
        <div class="card">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="bi bi-coin me-2"></i>Punten geschiedenis — Huidig saldo: <strong class="text-warning"><?= number_format($totalPoints) ?></strong></h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($points)): ?>
                    <p class="text-muted p-3 mb-0">Geen punten transacties.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Punten</th><th>Reden</th><th>Datum</th></tr></thead>
                        <tbody>
                            <?php foreach ($points as $pt): ?>
                            <tr>
                                <td>
                                    <strong class="<?= $pt['points'] > 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $pt['points'] > 0 ? '+' : '' ?><?= $pt['points'] ?>
                                    </strong>
                                </td>
                                <td><?= esc($pt['reason'] ?? '—') ?></td>
                                <td><small><?= $pt['created_at'] ? date('d-m-Y H:i', strtotime($pt['created_at'])) : '—' ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-shield-exclamation me-2"></i>Security events</h6>
                <a href="<?= base_url('admin/security/events?user_id=' . $user['id']) ?>" class="btn btn-outline-secondary btn-sm">Alles bekijken</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($events)): ?>
                    <p class="text-muted p-3 mb-0">Geen security events voor deze gebruiker.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Tijd</th><th>Type</th><th>Severity</th><th>Route</th><th>Details</th></tr></thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <?php $details = json_decode((string) ($event['details'] ?? '{}'), true) ?: []; ?>
                                <tr>
                                    <td><small><?= $event['created_at'] ? date('d-m-Y H:i', strtotime($event['created_at'])) : '—' ?></small></td>
                                    <td><code><?= esc($event['event_type']) ?></code></td>
                                    <td><span class="badge <?= ($event['severity'] ?? '') === 'critical' ? 'bg-danger' : (($event['severity'] ?? '') === 'error' ? 'bg-warning text-dark' : 'bg-secondary') ?>"><?= esc($event['severity']) ?></span></td>
                                    <td><small><?= esc($event['route'] ?: '—') ?></small></td>
                                    <td><small class="text-muted"><?= esc((string) ($details['ip'] ?? $details['email'] ?? $details['device_id'] ?? '—')) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
