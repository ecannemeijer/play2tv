<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<!-- Filters & Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= base_url('admin/users') ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Zoeken op e-mail</label>
                <input type="text" name="search" class="form-control" placeholder="user@example.com" value="<?= esc($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Premium status</label>
                <select name="premium" class="form-select">
                    <option value="">Alle</option>
                    <option value="1" <?= $premium === '1' ? 'selected' : '' ?>>Premium</option>
                    <option value="0" <?= $premium === '0' ? 'selected' : '' ?>>Geen premium</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Account status</label>
                <select name="active" class="form-select">
                    <option value="">Alle</option>
                    <option value="1" <?= $active === '1' ? 'selected' : '' ?>>Actief</option>
                    <option value="0" <?= $active === '0' ? 'selected' : '' ?>>Geblokkeerd</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Zoeken
                </button>
                <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- User Table -->
<div class="card">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-people me-2"></i>Gebruikers (<?= count($users) ?>)</h6>
        <a href="<?= base_url('admin/users/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i>Nieuwe gebruiker
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <p class="text-muted p-3 mb-0">Geen gebruikers gevonden.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>E-mail</th>
                            <th>Premium</th>
                            <th>Punten</th>
                            <th>Status</th>
                            <th>Registratie</th>
                            <th>Laatste login</th>
                            <th>Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="text-muted">#<?= $user['id'] ?></td>
                            <td><?= esc($user['email']) ?></td>
                            <td>
                                <?php if ($user['premium']): ?>
                                    <span class="badge badge-premium">
                                        <i class="bi bi-star-fill me-1"></i>Premium
                                    </span>
                                    <?php if ($user['premium_until']): ?>
                                        <br><small class="text-muted">t/m <?= date('d-m-Y', strtotime($user['premium_until'])) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Gratis</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= number_format((int)$user['total_points']) ?></strong></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success">Actief</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Geblokkeerd</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small">
                                <?= $user['created_at'] ? date('d-m-Y', strtotime($user['created_at'])) : '—' ?>
                            </td>
                            <td class="text-muted small">
                                <?= $user['last_login_at'] ? date('d-m-Y H:i', strtotime($user['last_login_at'])) : '—' ?>
                                <?php if ($user['last_login_ip']): ?>
                                    <br><code><?= esc($user['last_login_ip']) ?></code>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= base_url('admin/users/' . $user['id']) ?>" class="btn btn-sm btn-outline-info" title="Bekijken">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= base_url('admin/users/' . $user['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary" title="Bewerken">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= base_url('admin/users/' . $user['id'] . '/delete') ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       title="Verwijderen"
                                       onclick="return confirm('Gebruiker #<?= $user['id'] ?> permanent verwijderen?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
