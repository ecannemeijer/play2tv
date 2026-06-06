<?= $this->extend('admin/layout') ?>
<?= $this->section('content') ?>

<div class="topbar">
    <div>
        <a href="<?= base_url('admin/billing') ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Terug
        </a>
        <h1 class="d-inline"><?= esc($title) ?></h1>
    </div>
</div>

<?php $tx = $transaction; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><strong>Transactie details</strong></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <?php if ($tx['status'] === 'completed'): ?>
                            <span class="badge bg-success">Voltooid</span>
                        <?php elseif ($tx['status'] === 'refunded'): ?>
                            <span class="badge bg-warning">Terugbetaald</span>
                        <?php elseif ($tx['status'] === 'cancelled'): ?>
                            <span class="badge bg-danger">Geannuleerd</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= esc($tx['status']) ?></span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Type</dt>
                    <dd class="col-sm-8">
                        <?php if ($tx['plan_type'] === 'yearly'): ?>
                            <span class="badge bg-primary">Jaarlijks abonnement</span>
                        <?php elseif ($tx['plan_type'] === 'lifetime'): ?>
                            <span class="badge bg-success">Lifetime — eenmalig</span>
                        <?php else: ?>
                            <?= esc($tx['plan_type'] ?? '—') ?>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Product ID</dt>
                    <dd class="col-sm-8"><code><?= esc($tx['product_id']) ?></code></dd>

                    <dt class="col-sm-4">Duur</dt>
                    <dd class="col-sm-8"><?= esc($tx['premium_duration'] ?? '—') ?></dd>

                    <dt class="col-sm-4">Bedrag</dt>
                    <dd class="col-sm-8">
                        <?= esc($tx['amount'] ?? '—') ?>
                        <?= ! empty($tx['currency']) ? '<small class="text-muted">' . esc($tx['currency']) . '</small>' : '' ?>
                    </dd>

                    <dt class="col-sm-4">Google Order ID</dt>
                    <dd class="col-sm-8">
                        <?php if (! empty($tx['google_order_id'])): ?>
                            <code><?= esc($tx['google_order_id']) ?></code>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Aangemaakt</dt>
                    <dd class="col-sm-8"><?= esc($tx['created_at'] ?? '—') ?></dd>

                    <dt class="col-sm-4">Bijgewerkt</dt>
                    <dd class="col-sm-8"><?= esc($tx['updated_at'] ?? '—') ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><strong>Gebruiker</strong></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">E-mail</dt>
                    <dd class="col-sm-8">
                        <a href="<?= base_url('admin/users/' . $tx['user_id']) ?>">
                            <?= esc($tx['user_email'] ?? 'Onbekend') ?>
                        </a>
                    </dd>
                    <dt class="col-sm-4">User ID</dt>
                    <dd class="col-sm-8"><code><?= esc((string) $tx['user_id']) ?></code></dd>
                </dl>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><strong>Purchase Token</strong></div>
            <div class="card-body">
                <code style="word-break: break-all; font-size: 0.75rem;">
                    <?= esc($tx['purchase_token'] ?? '—') ?>
                </code>
            </div>
        </div>
    </div>
</div>

<!-- Raw JSON response -->
<?php if (! empty($tx['raw_response'])): ?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Raw API Request</strong>
        <button class="btn btn-sm btn-outline-secondary" onclick="toggleRaw()">
            <i class="bi bi-code-slash"></i> Toon/Verberg
        </button>
    </div>
    <div class="card-body" id="raw-json-block" style="display: none;">
        <pre class="mb-0" style="max-height: 400px; overflow-y: auto; font-size: 0.8rem;"><code><?= esc(json_encode($tx['raw_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></code></pre>
    </div>
</div>
<?php endif; ?>

<script>
function toggleRaw() {
    const block = document.getElementById('raw-json-block');
    block.style.display = block.style.display === 'none' ? 'block' : 'none';
}
</script>

<?= $this->endSection() ?>