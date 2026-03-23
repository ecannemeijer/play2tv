<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Admin Login — Play2TV') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(ellipse at 20% 50%, #1e1b4b 0%, #0f0f1a 50%, #0f0f1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            color: #e2e8f0;
        }
        .login-card {
            background: #1a1a2e;
            border: 1px solid #2d2d44;
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, .6);
        }
        .brand { font-size: 1.8rem; font-weight: 800; color: #a78bfa; margin-bottom: .25rem; }
        .brand span { color: #fff; }
        .subtitle { color: #94a3b8; font-size: .9rem; margin-bottom: 2rem; }
        .form-control {
            background: #0f0f1a;
            border-color: #2d2d44;
            color: #e2e8f0;
            padding: .75rem 1rem;
        }
        .form-control:focus {
            background: #0f0f1a;
            border-color: #7c3aed;
            color: #e2e8f0;
            box-shadow: 0 0 0 .25rem rgba(124, 58, 237, .25);
        }
        .form-label { color: #94a3b8; font-size: .85rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
        .btn-login {
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            border: none;
            color: #fff;
            padding: .85rem;
            font-weight: 700;
            font-size: 1rem;
            border-radius: 8px;
            width: 100%;
            transition: all .2s;
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(124, 58, 237, .4); color: #fff; }
        .alert-danger { background: #2d0a0a; border-color: #7f1d1d; color: #fca5a5; }
        .alert-success { background: #052e16; border-color: #166534; color: #86efac; }
        .footer-text { color: #94a3b8; font-size: .75rem; text-align: center; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">Play<span>2TV</span></div>
        <div class="subtitle">Beheer Panel — Beveiligde toegang</div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger mb-3" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= esc(session()->getFlashdata('error')) ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success mb-3" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?= esc(session()->getFlashdata('success')) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('admin/login') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label" for="username">
                    <i class="bi bi-person"></i> Gebruikersnaam
                </label>
                <input
                    type="text"
                    class="form-control"
                    id="username"
                    name="username"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <div class="mb-4">
                <label class="form-label" for="password">
                    <i class="bi bi-lock"></i> Wachtwoord
                </label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i>Inloggen
            </button>
        </form>

        <p class="footer-text">
            <i class="bi bi-shield-lock me-1"></i>
            Beveiligd via HTTPS &amp; CSRF-bescherming
        </p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
