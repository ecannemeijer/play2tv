<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 text-white">Admin beheer</h4>
    <div class="d-flex gap-2">
        <a href="<?= base_url('admin/admins/change-password') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-key"></i> Wachtwoord wijzigen
        </a>
        <a href="<?= base_url('admin/admins/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Nieuwe admin
        </a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card bg-dark border-secondary">
    <div class="card-body p-0">
        <table class="table table-dark table-hover mb-0">
            <thead class="table-secondary">
                <tr>
                    <th>#</th>
                    <th>Gebruikersnaam</th>
                    <th>Aangemaakt op</th>
                    <th class="text-end">Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admins)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Geen admins gevonden.</td></tr>
                <?php else: ?>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td class="text-muted"><?= (int) $admin['id'] ?></td>
                            <td>
                                <?= esc($admin['username']) ?>
                                <?php if ((int) $admin['id'] === (int) $currentAdmin): ?>
                                    <span class="badge bg-primary ms-1">Jij</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= esc($admin['created_at']) ?></td>
                            <td class="text-end">
                                <a href="<?= base_url('admin/admins/' . $admin['id'] . '/edit') ?>"
                                   class="btn btn-sm btn-outline-light me-1">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ((int) $admin['id'] !== (int) $currentAdmin): ?>
                                    <a href="<?= base_url('admin/admins/' . $admin['id'] . '/delete') ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Admin \'<?= esc($admin['username']) ?>\' verwijderen?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
