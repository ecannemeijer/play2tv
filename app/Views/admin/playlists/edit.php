<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= base_url('admin/playlists') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Terug
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header py-3">
                <h6 class="mb-0">
                    <i class="bi bi-pencil me-2"></i>Playlist bewerken: <?= esc($playlist['name']) ?>
                    <?php if ($playlist['is_active']): ?>
                        <span class="badge bg-success ms-2">Actief</span>
                    <?php endif; ?>
                </h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('admin/playlists/' . $playlist['id'] . '/edit') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Playlist naam</label>
                        <input type="text" name="name" class="form-control" value="<?= esc($playlist['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-upload me-1"></i>Nieuw M3U bestand uploaden (optioneel)
                        </label>
                        <input type="file" name="m3u_file" class="form-control" accept=".m3u,.m3u8,.txt">
                        <div class="form-text text-muted">Leeg laten om huidige inhoud te behouden, tenzij je de tekst hieronder aanpast.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-code me-1"></i>M3U inhoud
                        </label>
                        <textarea
                            name="m3u_content"
                            class="form-control"
                            rows="20"
                            style="font-family: monospace; font-size: .8rem;"
                        ><?= esc($playlist['m3u_content']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Opslaan
                    </button>
                    <a href="<?= base_url('admin/playlists') ?>" class="btn btn-outline-secondary ms-2">
                        Annuleren
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
