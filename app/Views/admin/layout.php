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
            --bg-main: #0f0f1a;
            --bg-panel: #1a1a2e;
            --bg-panel-strong: #16213e;
            --border-color: #2d2d44;
            --text-main: #e2e8f0;
            --text-soft: #cbd5e1;
            --text-muted-dark: #94a3b8;
            --text-faint: #64748b;
        }
        body {
            background: var(--bg-main);
            color: var(--text-main);
            font-family: 'Segoe UI', sans-serif;
        }
        body, p, li, dd, .card, .card-body, .card-title, .card-text,
        .form-label, .form-text, .form-check-label, .form-control,
        .form-select, .input-group-text, .page-link, .dropdown-menu,
        .dropdown-item, .list-group-item {
            color: var(--text-main);
        }
        a { color: #c4b5fd; }
        a:hover { color: #ddd6fe; }
        .text-muted, .form-text, small.text-muted, .small.text-muted,
        dt.text-muted, td.text-muted, p.text-muted, span.text-muted {
            color: var(--text-muted-dark) !important;
        }
        h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6,
        label, dt, th, strong {
            color: var(--text-main);
        }
        code {
            color: #ddd6fe;
            background: #1e1b4b;
            padding: .1em .35em;
            border-radius: 4px;
        }
        .btn-close { filter: invert(1) grayscale(1); }
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--bg-panel);
            position: fixed;
            top: 0; left: 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border-color);
            z-index: 1000;
        }
        .sidebar-brand {
            padding: 1.5rem 1.25rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: #a78bfa;
            border-bottom: 1px solid var(--border-color);
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
            border-top: 1px solid var(--border-color);
            font-size: .8rem;
            color: var(--text-faint);
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
            border-bottom: 1px solid var(--border-color);
        }
        .topbar h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
        .card {
            background: var(--bg-panel);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }
        .card-header {
            background: var(--bg-panel-strong);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }
        .stat-card {
            background: linear-gradient(135deg, #1e1b4b, #1a1a2e);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
        }
        .stat-card .stat-value { font-size: 2rem; font-weight: 800; color: #a78bfa; }
        .stat-card .stat-label { color: #94a3b8; font-size: .85rem; }
        .stat-card .stat-icon { font-size: 2.5rem; opacity: .4; }
        .table {
            color: var(--text-main);
            --bs-table-color: var(--text-main);
            --bs-table-bg: transparent;
            --bs-table-border-color: #1e2035;
            --bs-table-striped-color: var(--text-main);
            --bs-table-hover-color: var(--text-main);
        }
        .table thead th { border-color: var(--border-color); color: var(--text-muted-dark); font-size: .8rem; text-transform: uppercase; }
        .table tbody td { border-color: #1e2035; vertical-align: middle; color: var(--text-main); }
        .table tbody tr:hover { background: rgba(124, 58, 237, .08); }
        .badge-premium { background: linear-gradient(135deg, #7c3aed, #ec4899); }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .btn-outline-secondary { color: var(--text-muted-dark); border-color: #475569; }
        .btn-outline-secondary:hover { background: #1e293b; color: var(--text-main); border-color: #64748b; }
        .form-control, .form-select {
            background: var(--bg-main);
            border-color: var(--border-color);
            color: var(--text-main);
        }
        .form-control:focus, .form-select:focus {
            background: var(--bg-main);
            border-color: #7c3aed;
            color: var(--text-main);
            box-shadow: 0 0 0 .25rem rgba(124, 58, 237, .25);
        }
        .form-control::placeholder, .form-select::placeholder, textarea::placeholder {
            color: var(--text-faint);
        }
        .input-group-text, .page-link {
            background: var(--bg-panel);
            border-color: var(--border-color);
            color: var(--text-muted-dark);
        }
        .page-link:hover {
            background: var(--bg-panel-strong);
            color: var(--text-main);
        }
        .table-secondary,
        .table > :not(caption) > * > .table-secondary {
            --bs-table-bg: var(--bg-panel-strong);
            --bs-table-color: var(--text-main);
            color: var(--text-main) !important;
        }
        .nav-pills .nav-link { color: var(--text-muted-dark); }
        .nav-pills .nav-link.active { background: var(--primary); color: #fff; }
        .alert-success { background: #052e16; border-color: #166534; color: #86efac; }
        .alert-danger  { background: #2d0a0a; border-color: #7f1d1d; color: #fca5a5; }
        .alert-warning { background: #2d1f00; border-color: #92400e; color: #fcd34d; }
        canvas { max-height: 220px; }
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
        <a href="<?= base_url('admin/security/events') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'admin/security') ? 'active' : '' ?>">
            <i class="bi bi-shield-lock"></i> Security
        </a>
        <a href="<?= base_url('admin/diagnostics/logs') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'admin/diagnostics') ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i> Diagnostics Logs
        </a>
        <a href="<?= base_url('admin/telemetry') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'admin/telemetry') ? 'active' : '' ?>">
            <i class="bi bi-activity"></i> Telemetry
        </a>
        <a href="<?= base_url('admin/redis') ?>"
           class="nav-link <?= str_starts_with(uri_string(), 'admin/redis') ? 'active' : '' ?>">
            <i class="bi bi-database"></i> REDIS
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
