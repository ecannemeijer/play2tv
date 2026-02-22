<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 text-white">Wachtwoord wijzigen</h4>
    <a href="<?= base_url('admin/admins') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Terug
    </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (! empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= esc($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card bg-dark border-secondary" style="max-width: 480px;">
    <div class="card-body">
        <form method="post" action="<?= base_url('admin/admins/change-password') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label text-light">Huidig wachtwoord</label>
                <input type="password" name="current_password"
                       class="form-control bg-dark text-white border-secondary"
                       required autocomplete="current-password">
            </div>

            <div class="mb-3">
                <label class="form-label text-light">Nieuw wachtwoord</label>
                <input type="password" name="new_password"
                       class="form-control bg-dark text-white border-secondary"
                       required minlength="8" autocomplete="new-password">
            </div>

            <div class="mb-4">
                <label class="form-label text-light">Nieuw wachtwoord bevestigen</label>
                <input type="password" name="password_confirm"
                       class="form-control bg-dark text-white border-secondary"
                       required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-key"></i> Wachtwoord wijzigen
            </button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
