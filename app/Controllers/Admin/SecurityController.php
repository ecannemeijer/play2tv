<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\SecurityEventModel;
use CodeIgniter\Controller;

class SecurityController extends Controller
{
    private SecurityEventModel $events;

    public function __construct()
    {
        $this->events = new SecurityEventModel();
        helper(['url', 'form']);
    }

    public function events()
    {
        return $this->renderEvents(false);
    }

    public function suspicious()
    {
        return $this->renderEvents(true);
    }

    private function renderEvents(bool $suspiciousOnly)
    {
        $severity = trim((string) ($this->request->getGet('severity') ?? ''));
        $query = trim((string) ($this->request->getGet('q') ?? ''));
        $userId = (int) ($this->request->getGet('user_id') ?? 0);

        $events = $this->events->getRecentWithUsers(100, [
            'severity' => $severity,
            'query' => $query,
            'user_id' => $userId > 0 ? $userId : null,
            'suspicious_only' => $suspiciousOnly,
        ]);

        return view('admin/security/events', [
            'title' => $suspiciousOnly ? 'Verdachte Activiteit — Play2TV Admin' : 'Security Events — Play2TV Admin',
            'events' => $events,
            'severity' => $severity,
            'query' => $query,
            'userId' => $userId > 0 ? $userId : '',
            'suspiciousOnly' => $suspiciousOnly,
        ]);
    }
}