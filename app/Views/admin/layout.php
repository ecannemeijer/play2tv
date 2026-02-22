<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Play2TV Admin') ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 240px;
            --primary: #7c3aed;
            --primary-dark: #5b21b6;
        }
        body { background: #0f0f1a; color: #e2e8f0; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: #1a1a2e;
            position: fixed;
            top: 0; left: 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #2d2d44;
            z-index: 1000;
        }
        .sidebar-brand {
            padding: 1.5rem 1.25rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: #a78bfa;
            border-bottom: 1px solid #2d2d44;
            text-decoration: none;
        }
        .sidebar-brand span { color: #fff; }
        .sidebar-nav { flex: 1; padding: 1rem 0; }
        .sidebar-nav .nav-link {
            color: #94a3b8;
            padding: .6rem 1.25rem;
            display: flex;
            align-items: center;
            gap: .6rem;
            border-radius: 6px;
            margin: .1rem .75rem;
            transition: all .2s;
            text-decoration: none;
        }
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background: rgba(124, 58, 237, .25);
            color: #fff;
        }
        .sidebar-nav .nav-link i { font-size: 1.1rem; }
        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid #2d2d44;
            font-size: .8rem;
            color: #64748b;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 1.5rem 2rem;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #2d2d44;
        }
        .topbar h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
        .card {
            background: #1a1a2e;
            border: 1px solid #2d2d44;
            border-radius: 12px;
        }
        .card-header { background: #16213e; border-bottom: 1px solid #2d2d44; }
        .stat-card {
            background: linear-gradient(135deg, #1e1b4b, #1a1a2e);
            border: 1px solid #2d2d44;
            border-radius: 12px;
            padding: 1.25rem;
        }
        .stat-card .stat-value { font-size: 2rem; font-weight: 800; color: #a78bfa; }
        .stat-card .stat-label { color: #94a3b8; font-size: .85rem; }
        .stat-card .stat-icon { font-size: 2.5rem; opacity: .4; }
        .table { color: #e2e8f0; }
        .table thead th { border-color: #2d2d44; color: #94a3b8; font-size: .8rem; text-transform: uppercase; }
        .table tbody td { border-color: #1e2035; vertical-align: middle; }
        .table tbody tr:hover { background: rgba(124, 58, 237, .08); }
        .badge-premium { background: linear-gradient(135deg, #7c3aed, #ec4899); }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .form-control, .form-select {
            background: #0f0f1a;
            border-color: #2d2d44;
            color: #e2e8f0;
        }
        .form-control:focus, .form-select:focus {
            background: #0f0f1a;
            border-color: #7c3aed;
            color: #e2e8f0;
            box-shadow: 0 0 0 .25rem rgba(124, 58, 237, .25);
        }
        .alert-success { background: #052e16; border-color: #166534; color: #86efac; }
        .alert-danger  { background: #2d0a0a; border-color: #7f1d1d; color: #fca5a5; }
        .alert-warning { background: #2d1f00; border-color: #92400e; color: #fcd34d; }
        canvas { max-height: 220px; }
        /* ── Dark theme overrides for Bootstrap utilities ── */
        .text-muted, .form-text { color: #94a3b8 !important; }
        label, .form-label { color: #cbd5e1; }
        code { color: #a78bfa; background: #1e1b4b; padding: .1em .35em; border-radius: 4px; }
        .btn-close { filter: invert(1) grayscale(1); }
        dt { color: #94a3b8; }
        dd { color: #e2e8f0; }
        .card-text, p, li { color: #cbd5e1; }
        h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 { color: #f1f5f9; }
        small { color: #94a3b8; }
        .input-group-text { background: #1a1a2e; border-color: #2d2d44; color: #94a3b8; }
        /* Outline buttons readable on dark bg */
        .btn-outline-secondary { color: #94a3b8; border-color: #475569; }
        .btn-outline-secondary:hover { background: #1e293b; color: #e2e8f0; border-color: #64748b; }
        .btn-outline-info    { color: #38bdf8; border-color: #38bdf8; }
        .btn-outline-info:hover { background: rgba(56,189,248,.15); color: #38bdf8; }
        .btn-outline-primary { color: #a78bfa; border-color: #7c3aed; }
        .btn-outline-primary:hover { background: rgba(124,58,237,.2); color: #a78bfa; }
        .btn-outline-danger  { color: #f87171; border-color: #dc2626; }
        .btn-outline-danger:hover { background: rgba(220,38,38,.15); color: #f87171; }
        .btn-outline-success { color: #4ade80; border-color: #16a34a; }
        .btn-outline-success:hover { background: rgba(22,163,74,.15); color: #4ade80; }
        .btn-outline-warning { color: #fbbf24; border-color: #d97706; }
        .btn-outline-warning:hover { background: rgba(217,119,6,.15); color: #fbbf24; }
        /* Table striping override */
        .table-striped > tbody > tr:nth-of-type(odd) { --bs-table-accent-bg: rgba(255,255,255,.03); }
        /* Nav pills/tabs */
        .nav-pills .nav-link { color: #94a3b8; } 
        .nav-pills .nav-link.active { background: #7c3aed; color: #fff; }
        /* Form placeholder */
        ::placeholder { color: #475569 !important; }
        /* Pagination */
        .page-link { background: #1a1a2e; border-color: #2d2d44; color: #94a3b8; }
        .page-link:hover { background: #16213e; color: #e2e8f0; }
        /* Section dividers */
        .section-title { color: #a78bfa; font-size: .75rem; text-transform: uppercase; letter-spacing: .1em; font-weight: 700; margin-bottom: .5rem; padding-bottom: .25rem; border-bottom: 1px solid #2d2d44; }
    </style>
    <?= $this->renderSection('head') ?>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <a href="<?= base_url('admin/dashboard') ?>" class="sidebar-brand">
        Play<span>2TV</span> Admin
    </a>
    <div class="sidebar-nav">
        <a href="<?= base_url('admin/dashboard') ?>"
           class="nav-link <?= (uri_string() === 'admin/dashboard') ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="<?= base_url('admin/users') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'admin/users') ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Gebruikers
        </a>
        <a href="<?= base_url('admin/playlists') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'admin/playlists') ? 'active' : '' ?>">
            <i class="bi bi-collection-play"></i> Playlists
        </a>
    </div>
    <div class="sidebar-footer">
        Ingelogd als <strong><?= esc(session()->get('admin_username') ?? '') ?></strong><br>
        <a href="<?= base_url('admin/logout') ?>" class="text-danger text-decoration-none">
            <i class="bi bi-box-arrow-right"></i> Uitloggen
        </a>
    </div>
</nav>

<!-- Main -->
<main class="main-content">
    <div class="topbar">
        <h1><?= esc($title ?? 'Admin Panel') ?></h1>
        <span class="text-muted small"><?= date('d M Y, H:i') ?></span>
    </div>

    <!-- Flash messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>
</main>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
