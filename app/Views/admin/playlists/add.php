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
                <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Playlist toevoegen</h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('admin/playlists/add') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Playlist naam <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Bijv. Hoofd Playlist NL" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-upload me-1"></i>M3U bestand uploaden
                        </label>
                        <input type="file" name="m3u_file" class="form-control" accept=".m3u,.m3u8,.txt">
                        <div class="form-text text-muted">Ondersteunde formaten: .m3u, .m3u8, .txt</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-code me-1"></i>Of plak M3U inhoud
                        </label>
                        <textarea
                            name="m3u_content"
                            class="form-control"
                            rows="15"
                            placeholder="#EXTM3U&#10;#EXTINF:-1 tvg-id=&quot;channel1&quot; tvg-name=&quot;Channel 1&quot;,Channel 1&#10;http://stream.example.com/live/user/pass/1.ts"
                            style="font-family: monospace; font-size: .8rem;"
                        ></textarea>
                        <div class="form-text text-muted">Moet beginnen met <code>#EXTM3U</code></div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-upload me-1"></i>Playlist opslaan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
