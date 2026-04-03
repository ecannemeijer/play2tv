<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Libraries\XtreamDiagnosticsService;
use App\Models\AuthRefreshTokenModel;
use App\Models\SecurityEventModel;
use App\Models\UserModel;
use App\Models\UserSettingsModel;
use App\Models\UserDeviceModel;
use App\Models\UserIpsLogModel;
use App\Models\WatchHistoryModel;
use App\Models\StorePointsModel;
use CodeIgniter\Controller;

/**
 * UserController (Admin)
 *
 * Full user management for the admin panel.
 *
 * Routes (all behind /admin prefix, protected by AdminAuthFilter):
 *   GET  /admin/users            → List all users (search + filter)
 *   GET  /admin/users/create     → Show create user form
 *   POST /admin/users/create     → Create user
 *   GET  /admin/users/{id}       → View user detail
 *   GET  /admin/users/{id}/edit  → Edit user form
 *   POST /admin/users/{id}/edit  → Save user changes
 *   GET  /admin/users/{id}/delete → Delete user
 */
class UserController extends Controller
{
    private UserModel         $userModel;
    private UserSettingsModel $settingsModel;
    private UserDeviceModel   $deviceModel;
    private UserIpsLogModel   $ipsModel;
    private WatchHistoryModel $historyModel;
    private StorePointsModel  $pointsModel;
    private AuthRefreshTokenModel $refreshTokenModel;
    private SecurityEventModel $securityEventModel;
    private XtreamDiagnosticsService $xtreamDiagnostics;

    public function __construct()
    {
        $this->userModel   = new UserModel();
        $this->settingsModel = new UserSettingsModel();
        $this->deviceModel = new UserDeviceModel();
        $this->ipsModel    = new UserIpsLogModel();
        $this->historyModel = new WatchHistoryModel();
        $this->pointsModel  = new StorePointsModel();
        $this->refreshTokenModel = new AuthRefreshTokenModel();
        $this->securityEventModel = new SecurityEventModel();
        $this->xtreamDiagnostics = new XtreamDiagnosticsService();
        helper(['url', 'form']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/users
    // Query params: ?search=email&premium=1&active=1
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $search  = trim($this->request->getGet('search') ?? '');
        $premium = $this->request->getGet('premium') ?? '';
        $active  = $this->request->getGet('active') ?? '';

        $builder = $this->userModel->db->table('users u')
            ->select('u.*, COALESCE(SUM(sp.points), 0) AS total_points')
            ->join('store_points sp', 'sp.user_id = u.id', 'left')
            ->groupBy('u.id')
            ->orderBy('u.created_at', 'DESC');

        if ($search !== '') {
            $builder->like('u.email', $search);
        }

        if ($premium !== '') {
            $builder->where('u.premium', (int) $premium);
        }

        if ($active !== '') {
            $builder->where('u.is_active', (int) $active);
        }

        $users = $builder->get()->getResultArray();

        return view('admin/users/index', [
            'title'   => 'Gebruikers — Play2TV Admin',
            'users'   => $users,
            'search'  => $search,
            'premium' => $premium,
            'active'  => $active,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/users/create
    // ─────────────────────────────────────────────────────────────────────────
    public function create()
    {
        return view('admin/users/create', [
            'title' => 'Gebruiker aanmaken — Play2TV Admin',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/users/create
    // Body: email, password, premium, premium_until, is_active, xtream_*
    // ─────────────────────────────────────────────────────────────────────────
    public function store()
    {
        $email           = strtolower(trim((string) ($this->request->getPost('email') ?? '')));
        $password        = (string) ($this->request->getPost('password') ?? '');
        $premium         = (int) ($this->request->getPost('premium') ?? 0);
        $isActive        = (int) ($this->request->getPost('is_active') ?? 1);
        $premiumUntil    = $this->request->getPost('premium_until') ?: null;
        $xtreamServer    = trim((string) ($this->request->getPost('xtream_server') ?? ''));
        $xtreamUsername  = trim((string) ($this->request->getPost('xtream_username') ?? ''));
        $xtreamPassword  = trim((string) ($this->request->getPost('xtream_password') ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Ongeldig e-mailadres.');
        }

        if (strlen($password) < 8) {
            return redirect()->back()->withInput()->with('error', 'Wachtwoord moet minimaal 8 tekens bevatten.');
        }

        if ($this->userModel->findByEmail($email)) {
            return redirect()->back()->withInput()->with('error', 'Dit e-mailadres is al in gebruik.');
        }

        if ($xtreamServer !== '' && ! filter_var($xtreamServer, FILTER_VALIDATE_URL)) {
            return redirect()->back()->withInput()->with('error', 'Ongeldige Xtream server URL.');
        }

        $userId = $this->userModel->insert([
            'email'            => $email,
            'password'         => $password,
            'premium'          => $premium,
            'premium_until'    => $premium ? $premiumUntil : null,
            'is_active'        => $isActive,
            'xtream_server'    => $xtreamServer !== '' ? $xtreamServer : null,
            'xtream_username'  => $xtreamUsername !== '' ? $xtreamUsername : null,
            'xtream_password'  => $xtreamPassword !== '' ? $xtreamPassword : null,
        ]);

        if (! $userId) {
            return redirect()->back()->withInput()->with('error', 'Gebruiker aanmaken mislukt.');
        }

        $subtitleSettings = $this->collectSubtitleSettings();
        if ($this->hasSubtitleSettings($subtitleSettings)) {
            $this->settingsModel->saveSettings((int) $userId, $subtitleSettings);
        }

        return redirect()->to(base_url('admin/users/' . $userId))->with('success', 'Gebruiker aangemaakt.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/users/{id}
    // ─────────────────────────────────────────────────────────────────────────
    public function view($id)
    {
        $user = $this->userModel->find($id);

        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Gebruiker niet gevonden.');
        }

        $devices = $this->deviceModel->getDevicesForUser((int) $id);
        $ips     = $this->ipsModel->getLogsForUser((int) $id);
        $history = $this->historyModel->getHistory((int) $id, 20);
        $points  = $this->pointsModel->getHistory((int) $id, 20);
        $total   = $this->pointsModel->getTotalPoints((int) $id);
        $tokens  = $this->refreshTokenModel->getRecentForUser((int) $id, 20);
        $events  = $this->securityEventModel->getRecentWithUsers(12, ['user_id' => (int) $id]);

        return view('admin/users/view', [
            'title'      => 'Gebruiker #' . $id . ' — Play2TV Admin',
            'user'       => $user,
            'devices'    => $devices,
            'ips'        => $ips,
            'history'    => $history,
            'points'     => $points,
            'totalPoints' => $total,
            'tokens'     => $tokens,
            'events'     => $events,
            'xtreamDiagnostics' => session()->getFlashdata('xtreamDiagnostics'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/users/{id}/edit
    // ─────────────────────────────────────────────────────────────────────────
    public function edit($id)
    {
        $user = $this->userModel->find($id);

        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Gebruiker niet gevonden.');
        }

        return view('admin/users/edit', [
            'title'    => 'Gebruiker bewerken — Play2TV Admin',
            'user'     => $user,
            'settings' => $this->settingsModel->getSettings((int) $id),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/users/{id}/edit
    // Body: email, password (optional), premium, premium_until, is_active, xtream_*
    // ─────────────────────────────────────────────────────────────────────────
    public function update($id)
    {
        $user = $this->userModel->find($id);

        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Gebruiker niet gevonden.');
        }

        $xtreamServer = trim((string) ($this->request->getPost('xtream_server') ?? ''));
        $xtreamUsername = trim((string) ($this->request->getPost('xtream_username') ?? ''));
        $xtreamPassword = trim((string) ($this->request->getPost('xtream_password') ?? ''));

        $data = [
            'email'         => trim($this->request->getPost('email') ?? $user['email']),
            'premium'       => (int) $this->request->getPost('premium'),
            'premium_until' => $this->request->getPost('premium_until') ?: null,
            'is_active'     => (int) $this->request->getPost('is_active'),
            'xtream_server'   => $xtreamServer !== '' ? $xtreamServer : null,
            'xtream_username' => $xtreamUsername !== '' ? $xtreamUsername : null,
            'xtream_password' => $xtreamPassword !== '' ? $xtreamPassword : null,
        ];

        // Only update password if a new one was submitted
        $newPassword = $this->request->getPost('password');
        if (! empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                return redirect()->back()->withInput()->with('error', 'Wachtwoord moet minimaal 8 tekens bevatten.');
            }
            $data['password'] = $newPassword; // Model callback will hash it
        }

        // Validate email
        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Ongeldig e-mailadres.');
        }

        if ($xtreamServer !== '' && ! filter_var($xtreamServer, FILTER_VALIDATE_URL)) {
            return redirect()->back()->withInput()->with('error', 'Ongeldige Xtream server URL.');
        }

        $this->userModel->update($id, $data);
        $this->settingsModel->saveSettings((int) $id, $this->collectSubtitleSettings($this->settingsModel->getSettings((int) $id)));

        return redirect()->to(base_url('admin/users/' . $id))->with('success', 'Gebruiker bijgewerkt.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/users/{id}/delete
    // ─────────────────────────────────────────────────────────────────────────
    public function delete($id)
    {
        $user = $this->userModel->find($id);

        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Gebruiker niet gevonden.');
        }

        $this->userModel->delete($id);

        return redirect()->to(base_url('admin/users'))->with('success', 'Gebruiker verwijderd.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/users/{id}/points
    // Add or deduct points manually from admin panel
    // ─────────────────────────────────────────────────────────────────────────
    public function addPoints($id)
    {
        $user = $this->userModel->find($id);

        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Gebruiker niet gevonden.');
        }

        $points = (int) $this->request->getPost('points');
        $reason = htmlspecialchars(strip_tags($this->request->getPost('reason') ?? 'Admin handmatig'));

        if ($points === 0) {
            return redirect()->back()->with('error', 'Punten mogen niet 0 zijn.');
        }

        $this->pointsModel->addPoints((int) $id, $points, $reason);

        return redirect()->to(base_url('admin/users/' . $id))->with('success', 'Punten bijgewerkt.');
    }

    public function forceLogout($id)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Gebruiker niet gevonden.');
        }

        $this->refreshTokenModel->revokeUserTokens((int) $id, 'admin_force_logout');
        $this->userModel->bumpAuthVersion((int) $id);

        return redirect()->to(base_url('admin/users/' . $id))->with('success', 'Alle tokens en sessies van deze gebruiker zijn ingetrokken.');
    }

    public function revokeToken($id, $tokenId)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Gebruiker niet gevonden.');
        }

        $token = $this->refreshTokenModel->where('id', (int) $tokenId)->where('user_id', (int) $id)->first();
        if (! $token) {
            return redirect()->to(base_url('admin/users/' . $id))->with('error', 'Token niet gevonden.');
        }

        $this->refreshTokenModel->revokeById((int) $tokenId, 'admin_revoked');

        return redirect()->to(base_url('admin/users/' . $id))->with('success', 'Token ingetrokken.');
    }

    public function renameDevice($id, $devicePk)
    {
        $device = $this->deviceModel->findOwnedDevice((int) $id, (int) $devicePk);
        if (! $device) {
            return redirect()->to(base_url('admin/users/' . $id))->with('error', 'Apparaat niet gevonden.');
        }

        $deviceName = trim((string) ($this->request->getPost('device_name') ?? ''));
        if ($deviceName === '') {
            return redirect()->to(base_url('admin/users/' . $id))->with('error', 'Apparaatnaam mag niet leeg zijn.');
        }

        $this->deviceModel->update((int) $devicePk, ['device_name' => mb_substr($deviceName, 0, 100)]);

        return redirect()->to(base_url('admin/users/' . $id))->with('success', 'Apparaatnaam bijgewerkt.');
    }

    public function deleteDevice($id, $devicePk)
    {
        $device = $this->deviceModel->findOwnedDevice((int) $id, (int) $devicePk);
        if (! $device) {
            return redirect()->to(base_url('admin/users/' . $id))->with('error', 'Apparaat niet gevonden.');
        }

        $this->refreshTokenModel->revokeByDeviceId((int) $id, (string) $device['device_id'], 'admin_device_removed');
        $this->deviceModel->delete((int) $devicePk);

        return redirect()->to(base_url('admin/users/' . $id))->with('success', 'Apparaat verwijderd en gekoppelde tokens ingetrokken.');
    }

    public function xtreamDiagnostics($id)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to(base_url('admin/users'))->with('error', 'Gebruiker niet gevonden.');
        }

        $diagnostics = $this->xtreamDiagnostics->run($user);
        $statusMessage = ($diagnostics['summary'] ?? 'error') === 'ok'
            ? 'Xtream diagnose succesvol uitgevoerd.'
            : 'Xtream diagnose uitgevoerd met waarschuwingen of fouten.';

        return redirect()
            ->to(base_url('admin/users/' . $id))
            ->with('xtreamDiagnostics', $diagnostics)
            ->with(($diagnostics['summary'] ?? 'error') === 'error' ? 'error' : 'success', $statusMessage);
    }

    /**
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function collectSubtitleSettings(array $existing = []): array
    {
        $settings = $existing;
        $settings['api_sync_opensubtitles_settings'] = $this->request->getPost('api_sync_opensubtitles_settings') === '1';

        foreach (['opensubtitles_api_key', 'subdl_api_key', 'opensubtitles_username', 'opensubtitles_password'] as $field) {
            $value = trim((string) ($this->request->getPost($field) ?? ''));

            if ($value === '') {
                unset($settings[$field]);
                continue;
            }

            $settings[$field] = $value;
        }

        return $settings;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function hasSubtitleSettings(array $settings): bool
    {
        return ! empty($settings['api_sync_opensubtitles_settings'])
            || ! empty($settings['opensubtitles_api_key'])
            || ! empty($settings['subdl_api_key'])
            || ! empty($settings['opensubtitles_username'])
            || ! empty($settings['opensubtitles_password']);
    }
}
