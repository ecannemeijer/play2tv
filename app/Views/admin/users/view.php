<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Terug naar lijst
    </a>
    <a href="<?= base_url('admin/users/' . $user['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm ms-2">
        <i class="bi bi-pencil me-1"></i>Bewerken
    </a>
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
                        <thead><tr><th>Device ID</th><th>IP</th><th>Gezien</th></tr></thead>
                        <tbody>
                            <?php foreach ($devices as $device): ?>
                            <tr>
                                <td><small><code><?= esc(substr($device['device_id'], 0, 12)) ?>...</code></small></td>
                                <td><small><?= esc($device['ip_address']) ?></small></td>
                                <td><small><?= $device['last_seen'] ? date('d-m H:i', strtotime($device['last_seen'])) : '—' ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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

</div>

<?= $this->endSection() ?>
