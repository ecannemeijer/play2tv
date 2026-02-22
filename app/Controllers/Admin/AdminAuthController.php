<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\AdminModel;
use CodeIgniter\Controller;

/**
 * AdminAuthController
 *
 * Handles admin panel authentication via session-based login.
 * NOT JWT — this uses server-side sessions with CSRF protection.
 *
 * Routes:
 *   GET  /admin/login  → Show login form
 *   POST /admin/login  → Process login
 *   GET  /admin/logout → Destroy session and redirect
 */
class AdminAuthController extends Controller
{
    private AdminModel $adminModel;

    public function __construct()
    {
        $this->adminModel = new AdminModel();
        helper(['url', 'form']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/login
    // ─────────────────────────────────────────────────────────────────────────
    public function loginForm()
    {
        // Redirect to dashboard if already logged in
        if (session()->get('admin_id')) {
            return redirect()->to(base_url('admin/dashboard'));
        }

        return view('admin/login', [
            'title' => 'Admin Login — Play2TV',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/login
    // Body: username, password (form POST)
    // ─────────────────────────────────────────────────────────────────────────
    public function loginProcess()
    {
        $username = trim($this->request->getPost('username') ?? '');
        $password = $this->request->getPost('password') ?? '';

        if (empty($username) || empty($password)) {
            return redirect()->back()->with('error', 'Gebruikersnaam en wachtwoord zijn verplicht.');
        }

        $admin = $this->adminModel->findByUsername($username);

        if (! $admin || ! $this->adminModel->verifyPassword($password, $admin['password'])) {
            // Log failed attempt
            log_message('warning', 'Mislukte admin login voor: ' . $username . ' van IP: ' . $this->request->getIPAddress());
            return redirect()->back()->with('error', 'Ongeldige inloggegevens.');
        }

        // Create session
        session()->set([
            'admin_id'       => $admin['id'],
            'admin_username' => $admin['username'],
            'admin_logged_in' => true,
        ]);

        log_message('info', 'Admin ingelogd: ' . $username);

        return redirect()->to(base_url('admin/dashboard'))->with('success', 'Welkom, ' . $username . '!');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/logout
    // ─────────────────────────────────────────────────────────────────────────
    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('admin/login'))->with('success', 'Uitgelogd.');
    }
}
