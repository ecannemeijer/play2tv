<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= base_url('admin/playlists/add') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Playlist toevoegen
    </a>
</div>

<div class="card">
    <div class="card-header py-3">
        <h6 class="mb-0"><i class="bi bi-collection-play me-2"></i>Playlists (<?= count($playlists) ?>)</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($playlists)): ?>
            <p class="text-muted p-3 mb-0">Nog geen playlists. <a href="<?= base_url('admin/playlists/add') ?>">Upload er een</a>.</p>
        <?php else: ?>
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Naam</th>
                        <th>Status</th>
                        <th>Grootte</th>
                        <th>Aangemaakt</th>
                        <th>Bijgewerkt</th>
                        <th>Acties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($playlists as $pl): ?>
                    <tr>
                        <td class="text-muted">#<?= $pl['id'] ?></td>
                        <td><strong><?= esc($pl['name']) ?></strong></td>
                        <td>
                            <?php if ($pl['is_active']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Actief</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactief</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?= $pl['content_size'] ? number_format((int)$pl['content_size'] / 1024, 1) . ' KB' : '—' ?>
                        </td>
                        <td class="text-muted small">
                            <?= $pl['created_at'] ? date('d-m-Y H:i', strtotime($pl['created_at'])) : '—' ?>
                        </td>
                        <td class="text-muted small">
                            <?= $pl['updated_at'] ? date('d-m-Y H:i', strtotime($pl['updated_at'])) : '—' ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <?php if (! $pl['is_active']): ?>
                                <a href="<?= base_url('admin/playlists/' . $pl['id'] . '/activate') ?>"
                                   class="btn btn-sm btn-outline-success"
                                   title="Activeren"
                                   onclick="return confirm('Deze playlist activeren?')">
                                    <i class="bi bi-check-circle"></i>
                                </a>
                                <?php endif; ?>
                                <a href="<?= base_url('admin/playlists/' . $pl['id'] . '/edit') ?>"
                                   class="btn btn-sm btn-outline-primary" title="Bewerken">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= base_url('admin/playlists/' . $pl['id'] . '/delete') ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   title="Verwijderen"
                                   onclick="return confirm('Playlist permanent verwijderen?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Info box -->
<div class="alert alert-warning mt-3" role="alert">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Let op:</strong> Alleen de <em>actieve</em> playlist wordt teruggegeven via
    <code>GET /api/playlist</code>. Alleen premium gebruikers kunnen de playlist ophalen.
</div>

<?= $this->endSection() ?>
