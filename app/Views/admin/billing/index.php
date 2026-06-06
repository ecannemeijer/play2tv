<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<div class="topbar">
    <h1><i class="bi bi-credit-card"></i> <?= esc($title) ?></h1>
    <span class="text-muted">Totaal: <?= esc((string) $stats['total']) ?> transacties</span>
</div>

<!-- Stats cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-value"><?= esc((string) $stats['total']) ?></div>
                <div class="stat-label">Totaal</div>
            </div>
            <i class="bi bi-receipt stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-value"><?= esc((string) $stats['yearly']) ?></div>
                <div class="stat-label">Jaarlijks</div>
            </div>
            <i class="bi bi-calendar-check stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-value"><?= esc((string) $stats['lifetime']) ?></div>
                <div class="stat-label">Lifetime</div>
            </div>
            <i class="bi bi-infinity stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-value"><?= esc((string) $stats['completed']) ?></div>
                <div class="stat-label">Voltooid</div>
            </div>
            <i class="bi bi-check-circle stat-icon"></i>
        </div>
    </div>
</div>

<!-- Transactions table -->
<div class="card">
    <div class="card-header">
        <strong>Alle transacties</strong>
    </div>
    <div class="card-body p-0">
        <?php if (empty($transactions)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-2">Nog geen transacties.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Gebruiker</th>
                            <th>Product ID</th>
                            <th>Type</th>
                            <th>Bedrag</th>
                            <th>Status</th>
                            <th>Datum</th>
                            <th class="text-end">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td><?= esc((string) $tx['id']) ?></td>
                                <td>
                                    <a href="<?= base_url('admin/users/' . $tx['user_id']) ?>">
                                        <?= esc($tx['user_email'] ?? 'Onbekend') ?>
                                    </a>
                                    <br><small class="text-muted">ID: <?= esc((string) $tx['user_id']) ?></small>
                                </td>
                                <td>
                                    <code><?= esc($tx['product_id']) ?></code>
                                </td>
                                <td>
                                    <?php if ($tx['plan_type'] === 'yearly'): ?>
                                        <span class="badge bg-primary">Jaarlijks</span>
                                    <?php elseif ($tx['plan_type'] === 'lifetime'): ?>
                                        <span class="badge bg-success">Lifetime</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc($tx['plan_type'] ?? '?') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= esc($tx['amount'] ?? '—') ?>
                                    <?= $tx['currency'] ? '<small class="text-muted">' . esc($tx['currency']) . '</small>' : '' ?>
                                </td>
                                <td>
                                    <?php if ($tx['status'] === 'completed'): ?>
                                        <span class="badge bg-success-subtle text-success">Voltooid</span>
                                    <?php elseif ($tx['status'] === 'refunded'): ?>
                                        <span class="badge bg-warning-subtle text-warning">Terugbetaald</span>
                                    <?php elseif ($tx['status'] === 'cancelled'): ?>
                                        <span class="badge bg-danger-subtle text-danger">Geannuleerd</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc($tx['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span title="<?= esc($tx['created_at'] ?? '') ?>">
                                        <?= esc(date('d-m-Y H:i', strtotime($tx['created_at'] ?? 'now'))) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('admin/billing/' . $tx['id']) ?>"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i> Bekijk
                                    </a>
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