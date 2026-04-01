<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Terug naar lijst
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="bi bi-person-plus me-2"></i>Nieuwe gebruiker aanmaken</h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('admin/users/create') ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3">

                        <!-- ── Account gegevens ─────────────────────────────── -->
                        <div class="col-12">
                            <div class="section-title"><i class="bi bi-person me-1"></i>Account gegevens</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label" for="email">E-mailadres <span class="text-danger">*</span></label>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                value="<?= esc(old('email')) ?>"
                                required
                                autofocus
                            >
                        </div>

                        <div class="col-md-12">
                            <label class="form-label" for="password">Wachtwoord <span class="text-danger">*</span></label>
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control"
                                placeholder="Minimaal 8 tekens"
                                autocomplete="new-password"
                                required
                            >
                        </div>

                        <!-- ── Premium & Status ─────────────────────────────── -->
                        <div class="col-12 mt-1">
                            <div class="section-title"><i class="bi bi-star me-1"></i>Status & Premium</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Account status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" selected>Actief</option>
                                <option value="0">Geblokkeerd</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Premium status</label>
                            <select name="premium" class="form-select">
                                <option value="0" selected>Geen premium</option>
                                <option value="1">Premium</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Premium geldig t/m</label>
                            <input
                                type="datetime-local"
                                name="premium_until"
                                class="form-control"
                                value="<?= esc(old('premium_until')) ?>"
                            >
                            <div class="form-text">Leeg = onbeperkt (als premium aan)</div>
                        </div>

                        <!-- ── Xtream Codes ─────────────────────────────────── -->
                        <div class="col-12 mt-1">
                            <div class="section-title"><i class="bi bi-tv me-1"></i>Xtream Codes (Android app playlist)</div>
                            <div class="text-muted small mb-2">
                                De Android app ontvangt deze gegevens na inloggen en voegt automatisch een Xtream playlist toe.
                                Laat leeg als de gebruiker geen Xtream toegang krijgt.
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Server URL</label>
                            <input
                                type="url"
                                name="xtream_server"
                                class="form-control"
                                placeholder="http://jouwserver.com:8080"
                                value="<?= esc(old('xtream_server')) ?>"
                            >
                            <div class="form-text">Inclusief protocol en poort. Bijv. <code>http://stream.example.com:8080</code></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Xtream gebruikersnaam</label>
                            <input
                                type="text"
                                name="xtream_username"
                                class="form-control"
                                placeholder="xtream_user"
                                value="<?= esc(old('xtream_username')) ?>"
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Xtream wachtwoord</label>
                            <input
                                type="text"
                                name="xtream_password"
                                class="form-control"
                                placeholder="xtream_pass"
                                value="<?= esc(old('xtream_password')) ?>"
                                autocomplete="off"
                            >
                            <div class="form-text">Let op: wordt leesbaar opgeslagen (Xtream vereist plain-text).</div>
                        </div>

                        <div class="col-12 mt-1">
                            <div class="section-title"><i class="bi bi-badge-cc me-1"></i>Subtitle providers</div>
                            <div class="text-muted small mb-2">
                                Optioneel: sla OpenSubtitles- en SubDL-instellingen direct op voor deze gebruiker.
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch mt-1">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="api_sync_opensubtitles_settings"
                                    name="api_sync_opensubtitles_settings"
                                    value="1"
                                    <?= old('api_sync_opensubtitles_settings') ? 'checked' : '' ?>
                                >
                                <label class="form-check-label" for="api_sync_opensubtitles_settings">
                                    API Synchronize subtitle provider settings
                                </label>
                            </div>
                            <div class="form-text text-muted">Sla de OpenSubtitles- en SubDL-instellingen op in user_settings voor deze gebruiker.</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">OpenSubtitles API key</label>
                            <input
                                type="text"
                                name="opensubtitles_api_key"
                                class="form-control"
                                value="<?= esc(old('opensubtitles_api_key')) ?>"
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">SubDL API key</label>
                            <input
                                type="text"
                                name="subdl_api_key"
                                class="form-control"
                                value="<?= esc(old('subdl_api_key')) ?>"
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">OpenSubtitles username</label>
                            <input
                                type="text"
                                name="opensubtitles_username"
                                class="form-control"
                                value="<?= esc(old('opensubtitles_username')) ?>"
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">OpenSubtitles password</label>
                            <input
                                type="text"
                                name="opensubtitles_password"
                                class="form-control"
                                value="<?= esc(old('opensubtitles_password')) ?>"
                                autocomplete="new-password"
                            >
                        </div>

                        <!-- ── Submit ───────────────────────────────────────── -->
                        <div class="col-12 pt-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-check me-1"></i>Gebruiker aanmaken
                            </button>
                            <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary">
                                Annuleren
                            </a>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
