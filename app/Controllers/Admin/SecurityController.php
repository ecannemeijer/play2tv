<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\SecurityEventModel;
use CodeIgniter\Controller;

class SecurityController extends Controller
{
    private const PER_PAGE = 25;

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

    public function clear()
    {
        $this->events->truncate();

        return redirect()
            ->back()
            ->with('success', 'Security events tabel is geleegd.');
    }

    private function renderEvents(bool $suspiciousOnly)
    {
        $severity = trim((string) ($this->request->getGet('severity') ?? ''));
        $query = trim((string) ($this->request->getGet('q') ?? ''));
        $userId = (int) ($this->request->getGet('user_id') ?? 0);
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));

        $filters = [
            'severity' => $severity,
            'query' => $query,
            'user_id' => $userId > 0 ? $userId : null,
            'suspicious_only' => $suspiciousOnly,
        ];

        $result = $this->events->getPagedWithUsers($page, self::PER_PAGE, $filters);
        $totalPages = max(1, (int) ceil($result['total'] / self::PER_PAGE));
        $baseQuery = array_filter([
            'q' => $query,
            'severity' => $severity,
            'user_id' => $userId > 0 ? (string) $userId : '',
        ], static fn ($value): bool => $value !== '');

        return view('admin/security/events', [
            'title' => $suspiciousOnly ? 'Verdachte Activiteit — Play2TV Admin' : 'Security Events — Play2TV Admin',
            'events' => $result['rows'],
            'totalEvents' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'totalPages' => $totalPages,
            'baseQuery' => $baseQuery,
            'severity' => $severity,
            'query' => $query,
            'userId' => $userId > 0 ? $userId : '',
            'suspiciousOnly' => $suspiciousOnly,
        ]);
    }
}