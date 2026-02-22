<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 text-white">Nieuwe admin aanmaken</h4>
    <a href="<?= base_url('admin/admins') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Terug
    </a>
</div>

<?php if (! empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= esc($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card bg-dark border-secondary">
    <div class="card-body">
        <form method="post" action="<?= base_url('admin/admins/create') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label text-light">Gebruikersnaam</label>
                <input type="text" name="username" class="form-control bg-dark text-white border-secondary"
                       value="<?= esc($old['username'] ?? '') ?>" required minlength="3" maxlength="50">
            </div>

            <div class="mb-3">
                <label class="form-label text-light">Wachtwoord</label>
                <input type="password" name="password" class="form-control bg-dark text-white border-secondary"
                       required minlength="8" autocomplete="new-password">
            </div>

            <div class="mb-4">
                <label class="form-label text-light">Wachtwoord bevestigen</label>
                <input type="password" name="password_confirm" class="form-control bg-dark text-white border-secondary"
                       required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Aanmaken
            </button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
