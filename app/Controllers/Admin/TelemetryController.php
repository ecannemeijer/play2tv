<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\TelemetryEventModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

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
        $filters = $this->readFilters('get');
        $selectedId = max(0, (int) ($this->request->getGet('id') ?? 0));

        $result = $this->telemetryEvents->getPaged($page, self::PER_PAGE, $filters);
        $totalPages = max(1, (int) ceil($result['total'] / self::PER_PAGE));
        $selectedEvent = $selectedId > 0 ? $this->telemetryEvents->find($selectedId) : null;
        $baseQuery = $this->buildBaseQuery($filters);

        return view('admin/telemetry/index', [
            'title' => 'Telemetry — Play2TV Admin',
            'events' => $result['rows'],
            'overview' => $this->telemetryEvents->getOverview(),
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'totalEvents' => $result['total'],
            'totalPages' => $totalPages,
            'baseQuery' => $baseQuery,
            'query' => $filters['query'],
            'type' => $filters['type'],
            'severity' => $filters['severity'],
            'appVersion' => $filters['app_version'],
            'selectedEvent' => $selectedEvent,
        ]);
    }

    public function exportCsv(): ResponseInterface
    {
        $rows = $this->telemetryEvents->getExportRows($this->readFilters('get'));

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="telemetry-export-' . date('Ymd-His') . '.csv"')
            ->setBody($this->buildCsv($rows));
    }

    public function exportJson(): ResponseInterface
    {
        $rows = $this->telemetryEvents->getExportRows($this->readFilters('get'));

        return $this->response
            ->setHeader('Content-Disposition', 'attachment; filename="telemetry-export-' . date('Ymd-His') . '.json"')
            ->setJSON([
                'exported_at' => date(DATE_ATOM),
                'count' => count($rows),
                'rows' => $rows,
            ]);
    }

    public function delete(): ResponseInterface
    {
        $eventId = max(0, (int) ($this->request->getPost('id') ?? 0));
        if ($eventId <= 0) {
            return redirect()->back()->with('error', 'Geen telemetry event geselecteerd.');
        }

        $deleted = $this->telemetryEvents->delete($eventId);
        if (! $deleted) {
            return redirect()->back()->with('error', 'Telemetry event kon niet worden verwijderd.');
        }

        return redirect()->to(base_url('admin/telemetry'))->with('success', 'Telemetry event verwijderd.');
    }

    public function deleteFiltered(): ResponseInterface
    {
        $filters = $this->readFilters('post');
        if ($this->buildBaseQuery($filters) === []) {
            return redirect()->back()->with('error', 'Gebruik eerst minimaal één filter voordat je gefilterde telemetry verwijdert.');
        }

        $deletedCount = $this->telemetryEvents->deleteByFilters($filters);

        return redirect()->to(base_url('admin/telemetry'))->with('success', sprintf('%d telemetry events verwijderd op basis van de huidige filters.', $deletedCount));
    }

    public function deleteAll(): ResponseInterface
    {
        $this->telemetryEvents->truncate();

        return redirect()->to(base_url('admin/telemetry'))->with('success', 'Alle telemetry events zijn verwijderd.');
    }

    public function prune(): ResponseInterface
    {
        $days = max(1, (int) ($this->request->getPost('days') ?? 30));
        $deletedCount = $this->telemetryEvents->pruneOlderThanDays($days);

        return redirect()->to(base_url('admin/telemetry'))->with('success', sprintf('%d telemetry events ouder dan %d dagen verwijderd.', $deletedCount, $days));
    }

    /**
     * @return array{query: string, type: string, severity: string, app_version: string}
     */
    private function readFilters(string $source = 'get'): array
    {
        $reader = $source === 'post'
            ? fn (string $key): string => trim((string) ($this->request->getPost($key) ?? ''))
            : fn (string $key): string => trim((string) ($this->request->getGet($key) ?? ''));

        return [
            'query' => $reader('q'),
            'type' => $reader('type'),
            'severity' => $reader('severity'),
            'app_version' => $reader('app_version'),
        ];
    }

    /**
     * @param array{query: string, type: string, severity: string, app_version: string} $filters
     * @return array<string, string>
     */
    private function buildBaseQuery(array $filters): array
    {
        return array_filter([
            'q' => $filters['query'],
            'type' => $filters['type'],
            'severity' => $filters['severity'],
            'app_version' => $filters['app_version'],
        ], static fn ($value): bool => $value !== '');
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function buildCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        fputcsv($handle, [
            'id',
            'created_at',
            'client_timestamp',
            'event_type',
            'severity',
            'app_version',
            'app_code',
            'device_name',
            'android_version',
            'channel_name',
            'last_action',
            'stream_type',
            'data_json',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['id'] ?? '',
                $row['created_at'] ?? '',
                $row['client_timestamp'] ?? '',
                $row['event_type'] ?? '',
                $row['severity'] ?? '',
                $row['app_version'] ?? '',
                $row['app_code'] ?? '',
                $row['device_name'] ?? '',
                $row['android_version'] ?? '',
                $row['channel_name'] ?? '',
                $row['last_action'] ?? '',
                $row['stream_type'] ?? '',
                $row['data_json'] ?? '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }
}