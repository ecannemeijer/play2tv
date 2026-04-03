<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-value"><?= number_format($totalUsers) ?></div>
                <div class="stat-label"><i class="bi bi-people me-1"></i>Totaal gebruikers</div>
            </div>
            <i class="bi bi-people stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-value"><?= number_format($activeUsers24h) ?></div>
                <div class="stat-label"><i class="bi bi-activity me-1"></i>Actief (24u)</div>
            </div>
            <i class="bi bi-activity stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-value" style="color:#ec4899"><?= number_format($premiumUsers) ?></div>
                <div class="stat-label"><i class="bi bi-star me-1"></i>Premium gebruikers</div>
            </div>
            <i class="bi bi-star stat-icon" style="color:#ec4899"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-value" style="color:#34d399"><?= number_format($totalWatchEvents) ?></div>
                <div class="stat-label"><i class="bi bi-play-circle me-1"></i>Watch events</div>
            </div>
            <i class="bi bi-play-circle stat-icon" style="color:#34d399"></i>
        </div>
    </div>
</div>

<!-- Second stats row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-value" style="color:#fbbf24"><?= number_format($totalPoints) ?></div>
            <div class="stat-label"><i class="bi bi-coin me-1"></i>Totaal punten uitgegeven</div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>System Health</h6>
        <small class="text-muted">DB, Redis, cache en prune-backlog</small>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($health as $item): ?>
                <?php
                    $badgeClass = match ($item['status']) {
                        'ok' => 'bg-success',
                        'warning' => 'bg-warning text-dark',
                        default => 'bg-danger',
                    };
                ?>
                <div class="col-md-6 col-xl-3">
                    <div class="border rounded-3 p-3 h-100" style="border-color:#2d2d44 !important; background:#141427;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong><?= esc($item['label']) ?></strong>
                            <span class="badge <?= $badgeClass ?>"><?= esc(strtoupper($item['status'])) ?></span>
                        </div>
                        <div class="small mb-2"><?= esc($item['message']) ?></div>
                        <?php if (! empty($item['meta'])): ?>
                            <ul class="small text-muted mb-0 ps-3">
                                <?php foreach ($item['meta'] as $key => $value): ?>
                                    <li><?= esc((string) $key) ?>: <?= esc(is_scalar($value) ? (string) $value : json_encode($value)) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="bi bi-person-plus me-2"></i>Nieuwe registraties (7 dagen)</h6>
            </div>
            <div class="card-body">
                <canvas id="registrationsChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="bi bi-play-circle me-2"></i>Watch events (7 dagen)</h6>
            </div>
            <div class="card-body">
                <canvas id="watchChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Most watched content -->
<div class="card">
    <div class="card-header py-3">
        <h6 class="mb-0"><i class="bi bi-trophy me-2"></i>Meest bekeken content (top 10)</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($mostWatched)): ?>
            <p class="text-muted p-3 mb-0">Nog geen kijkgeschiedenis beschikbaar.</p>
        <?php else: ?>
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Content Type</th>
                        <th>Content ID</th>
                        <th>Watch Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mostWatched as $i => $item): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <span class="badge <?= $item['content_type'] === 'movie' ? 'bg-info' : 'bg-success' ?>">
                                <?= esc($item['content_type']) ?>
                            </span>
                        </td>
                        <td><?= esc($item['content_id']) ?></td>
                        <td>
                            <strong><?= number_format($item['watch_count']) ?></strong>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const chartDefaults = {
    responsive: true,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#1a1a2e',
            borderColor: '#7c3aed',
            borderWidth: 1,
            titleColor: '#a78bfa',
            bodyColor: '#e2e8f0',
        }
    },
    scales: {
        x: {
            ticks: { color: '#94a3b8' },
            grid:  { color: '#1e2035' }
        },
        y: {
            ticks: { color: '#94a3b8' },
            grid:  { color: '#1e2035' },
            beginAtZero: true,
        }
    }
};

// Registrations chart
new Chart(document.getElementById('registrationsChart'), {
    type: 'bar',
    data: {
        labels: <?= $chartDays ?>,
        datasets: [{
            label: 'Registraties',
            data: <?= $chartRegCounts ?>,
            backgroundColor: 'rgba(124, 58, 237, 0.6)',
            borderColor: '#7c3aed',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: chartDefaults
});

// Watch events chart
new Chart(document.getElementById('watchChart'), {
    type: 'line',
    data: {
        labels: <?= $chartDays ?>,
        datasets: [{
            label: 'Watch events',
            data: <?= $chartWatchCounts ?>,
            borderColor: '#34d399',
            backgroundColor: 'rgba(52, 211, 153, 0.15)',
            borderWidth: 2,
            pointBackgroundColor: '#34d399',
            tension: 0.4,
            fill: true,
        }]
    },
    options: chartDefaults
});
</script>
<?= $this->endSection() ?>
