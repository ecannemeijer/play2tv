<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= base_url('admin/users/' . $user['id']) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Terug naar gebruiker
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header py-3">
                <h6 class="mb-0"><i class="bi bi-pencil me-2"></i>Gebruiker #<?= $user['id'] ?> bewerken</h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('admin/users/' . $user['id'] . '/edit') ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <!-- Email -->
                        <div class="col-md-12">
                            <label class="form-label">E-mailadres</label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="<?= esc($user['email']) ?>"
                                required
                            >
                        </div>

                        <!-- Password -->
                        <div class="col-md-12">
                            <label class="form-label">Nieuw wachtwoord</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Laat leeg om wachtwoord niet te wijzigen"
                                autocomplete="new-password"
                            >
                            <div class="form-text text-muted">Minimaal 8 tekens. Laat leeg om het huidige wachtwoord te behouden.</div>
                        </div>

                        <!-- Premium -->
                        <div class="col-md-4">
                            <label class="form-label">Premium status</label>
                            <select name="premium" class="form-select">
                                <option value="0" <?= ! $user['premium'] ? 'selected' : '' ?>>Geen premium</option>
                                <option value="1" <?= $user['premium'] ? 'selected' : '' ?>>Premium</option>
                            </select>
                        </div>

                        <!-- Premium until -->
                        <div class="col-md-4">
                            <label class="form-label">Premium geldig t/m</label>
                            <input
                                type="datetime-local"
                                name="premium_until"
                                class="form-control"
                                value="<?= $user['premium_until'] ? date('Y-m-d\TH:i', strtotime($user['premium_until'])) : '' ?>"
                            >
                            <div class="form-text text-muted">Leeg = onbeperkt (als premium aan)</div>
                        </div>

                        <!-- Active -->
                        <div class="col-md-4">
                            <label class="form-label">Account status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" <?= $user['is_active'] ? 'selected' : '' ?>>Actief</option>
                                <option value="0" <?= ! $user['is_active'] ? 'selected' : '' ?>>Geblokkeerd</option>
                            </select>
                        </div>

                        <div class="col-12 pt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Opslaan
                            </button>
                            <a href="<?= base_url('admin/users/' . $user['id']) ?>" class="btn btn-outline-secondary ms-2">
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
