<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\TelemetryEventModel;
use CodeIgniter\Controller;

class TelemetryController extends Controller
{
    private const PER_PAGE = 50;

    private TelemetryEventModel $telemetryEvents;

    public function __construct()
    {
        $this->telemetryEvents = new TelemetryEventModel();
        helper(['url', 'form']);
    }

    public function index(): string
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $query = trim((string) ($this->request->getGet('q') ?? ''));
        $type = trim((string) ($this->request->getGet('type') ?? ''));
        $severity = trim((string) ($this->request->getGet('severity') ?? ''));
        $appVersion = trim((string) ($this->request->getGet('app_version') ?? ''));
        $selectedId = max(0, (int) ($this->request->getGet('id') ?? 0));

        $filters = [
            'query' => $query,
            'type' => $type,
            'severity' => $severity,
            'app_version' => $appVersion,
        ];

        $result = $this->telemetryEvents->getPaged($page, self::PER_PAGE, $filters);
        $totalPages = max(1, (int) ceil($result['total'] / self::PER_PAGE));
        $selectedEvent = $selectedId > 0 ? $this->telemetryEvents->find($selectedId) : null;

        $baseQuery = array_filter([
            'q' => $query,
            'type' => $type,
            'severity' => $severity,
            'app_version' => $appVersion,
        ], static fn ($value): bool => $value !== '');

        return view('admin/telemetry/index', [
            'title' => 'Telemetry — Play2TV Admin',
            'events' => $result['rows'],
            'overview' => $this->telemetryEvents->getOverview(),
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'totalEvents' => $result['total'],
            'totalPages' => $totalPages,
            'baseQuery' => $baseQuery,
            'query' => $query,
            'type' => $type,
            'severity' => $severity,
            'appVersion' => $appVersion,
            'selectedEvent' => $selectedEvent,
        ]);
    }
}