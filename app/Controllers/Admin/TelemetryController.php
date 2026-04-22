<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Libraries\TelemetryConfigProvider;
use App\Models\TelemetryEventModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class TelemetryController extends Controller
{
    private const DEFAULT_PER_PAGE = 25;
    private const ALLOWED_PER_PAGE = [10, 25, 50, 100];

    private TelemetryEventModel $telemetryEvents;
    private TelemetryConfigProvider $telemetryConfig;

    public function __construct()
    {
        $this->telemetryEvents = new TelemetryEventModel();
        $this->telemetryConfig = new TelemetryConfigProvider();
        helper(['url', 'form']);
    }

    public function index(): string
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = $this->readPerPage('get');
        $filters = $this->readFilters('get');
        $groupOptions = $this->readGroupOptions();
        $selectedFingerprint = $this->readFingerprint('get');
        $selectedId = max(0, (int) ($this->request->getGet('id') ?? 0));

        $result = $this->telemetryEvents->getFingerprintGroups($page, $perPage, $filters, $groupOptions);
        $totalPages = max(1, (int) ceil($result['total'] / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $result = $this->telemetryEvents->getFingerprintGroups($page, $perPage, $filters, $groupOptions);
        }

        $baseQuery = $this->buildBaseQuery($filters, $groupOptions, $perPage);

        $selectedFingerprintSummary = $selectedFingerprint !== ''
            ? $this->telemetryEvents->getFingerprintGroupSummary($selectedFingerprint, $filters)
            : null;
        $selectedFingerprintEvents = $selectedFingerprintSummary !== null
            ? $this->telemetryEvents->getFingerprintEvents($selectedFingerprint, $filters)
            : [];
        $selectedEvent = $this->resolveSelectedEvent($selectedFingerprintEvents, $selectedId);

        return view('admin/telemetry/index', [
            'title' => 'Telemetry — Play2TV Admin',
            'fingerprintGroups' => $result['rows'],
            'overview' => $this->telemetryEvents->getOverview(),
            'page' => $page,
            'perPage' => $perPage,
            'perPageOptions' => self::ALLOWED_PER_PAGE,
            'totalEvents' => $this->telemetryEvents->countFiltered($filters),
            'totalFingerprints' => $result['total'],
            'totalPages' => $totalPages,
            'baseQuery' => $baseQuery,
            'query' => $filters['query'],
            'type' => $filters['type'],
            'severity' => $filters['severity'],
            'appVersion' => $filters['app_version'],
            'groupQuery' => $groupOptions['group_query'],
            'groupSort' => $groupOptions['sort'],
            'selectedFingerprint' => $selectedFingerprint,
            'selectedFingerprintSummary' => $selectedFingerprintSummary,
            'selectedFingerprintEvents' => $selectedFingerprintEvents,
            'selectedEvent' => $selectedEvent,
            'telemetryRemoteEnabled' => $this->telemetryConfig->getConfig()['telemetry_enabled'],
        ]);
    }

    public function toggleRemote(): ResponseInterface
    {
        $enabled = trim((string) ($this->request->getPost('enabled') ?? '')) === '1';
        if (! $this->telemetryConfig->setTelemetryEnabled($enabled)) {
            return redirect()->back()->with(
                'error',
                'Telemetry killswitch kon niet worden opgeslagen. Controleer of de writable-map schrijfbaar is.'
            );
        }

        return redirect()->back()->with(
            'success',
            $enabled
                ? 'Telemetry killswitch uitgeschakeld. Clients mogen weer telemetry verzenden.'
                : 'Telemetry killswitch geactiveerd. Clients krijgen telemetry_enabled = false.'
        );
    }

    public function exportCsv(): ResponseInterface
    {
        $rows = $this->telemetryEvents->getExportRows(
            $this->readFilters('get'),
            fingerprintKey: $this->readFingerprint('get') ?: null
        );

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="telemetry-export-' . date('Ymd-His') . '.csv"')
            ->setBody($this->buildCsv($rows));
    }

    public function exportJson(): ResponseInterface
    {
        $rows = $this->telemetryEvents->getExportRows(
            $this->readFilters('get'),
            fingerprintKey: $this->readFingerprint('get') ?: null
        );

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
        $perPage = $this->readPerPage('post');
        $filters = $this->readFilters('post');
        $groupOptions = $this->readGroupOptions('post');
        $fingerprint = $this->readFingerprint('post');
        $page = max(1, (int) ($this->request->getPost('page') ?? 1));
        if ($eventId <= 0) {
            return redirect()->back()->with('error', 'Geen telemetry event geselecteerd.');
        }

        $deleted = $this->telemetryEvents->delete($eventId);
        if (! $deleted) {
            return redirect()->back()->with('error', 'Telemetry event kon niet worden verwijderd.');
        }

        return redirect()->to($this->buildIndexUrl($filters, $groupOptions, $fingerprint, $page, $perPage))->with('success', 'Telemetry event verwijderd.');
    }

    public function deleteFiltered(): ResponseInterface
    {
        $perPage = $this->readPerPage('post');
        $filters = $this->readFilters('post');
        $groupOptions = $this->readGroupOptions('post');
        $fingerprint = $this->readFingerprint('post');
        $page = max(1, (int) ($this->request->getPost('page') ?? 1));
        if ($this->buildBaseQuery($filters, $groupOptions) === [] && $fingerprint === '') {
            return redirect()->back()->with('error', 'Gebruik eerst minimaal één filter voordat je gefilterde telemetry verwijdert.');
        }

        $deletedCount = $this->telemetryEvents->deleteByFilters($filters, $fingerprint ?: null);

        return redirect()->to($this->buildIndexUrl($filters, $groupOptions, '', $page, $perPage))->with('success', sprintf('%d telemetry events verwijderd op basis van de huidige selectie.', $deletedCount));
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
     * @return array{group_query: string, sort: string}
     */
    private function readGroupOptions(string $source = 'get'): array
    {
        $reader = $source === 'post'
            ? fn (string $key): string => trim((string) ($this->request->getPost($key) ?? ''))
            : fn (string $key): string => trim((string) ($this->request->getGet($key) ?? ''));

        $sort = $reader('sort');
        $allowedSorts = ['latest', 'errors', 'events', 'device', 'fingerprint'];

        return [
            'group_query' => $reader('group_query'),
            'sort' => in_array($sort, $allowedSorts, true) ? $sort : 'latest',
        ];
    }

    private function readFingerprint(string $source = 'get'): string
    {
        return trim((string) ($source === 'post'
            ? ($this->request->getPost('fingerprint') ?? '')
            : ($this->request->getGet('fingerprint') ?? '')));
    }

    private function readPerPage(string $source = 'get'): int
    {
        $value = max(1, (int) ($source === 'post'
            ? ($this->request->getPost('per_page') ?? self::DEFAULT_PER_PAGE)
            : ($this->request->getGet('per_page') ?? self::DEFAULT_PER_PAGE)));

        return in_array($value, self::ALLOWED_PER_PAGE, true) ? $value : self::DEFAULT_PER_PAGE;
    }

    /**
     * @param array{query: string, type: string, severity: string, app_version: string} $filters
     * @return array<string, string>
     */
    private function buildBaseQuery(array $filters, array $groupOptions = [], ?int $perPage = null): array
    {
        return array_filter([
            'q' => $filters['query'],
            'type' => $filters['type'],
            'severity' => $filters['severity'],
            'app_version' => $filters['app_version'],
            'group_query' => (string) ($groupOptions['group_query'] ?? ''),
            'sort' => ((string) ($groupOptions['sort'] ?? 'latest')) !== 'latest' ? (string) $groupOptions['sort'] : '',
            'per_page' => $perPage !== null && $perPage !== self::DEFAULT_PER_PAGE ? (string) $perPage : '',
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
            'fingerprint_hash',
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
                $row['fingerprint_hash'] ?? '',
                $row['data_json'] ?? '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    /**
     * @param list<array<string, mixed>> $events
     * @return array<string, mixed>|null
     */
    private function resolveSelectedEvent(array $events, int $selectedId): ?array
    {
        if ($events === []) {
            return null;
        }

        if ($selectedId > 0) {
            foreach ($events as $event) {
                if ((int) ($event['id'] ?? 0) === $selectedId) {
                    return $event;
                }
            }
        }

        return $events[0];
    }

    /**
     * @param array{query: string, type: string, severity: string, app_version: string} $filters
     */
    private function buildIndexUrl(array $filters, array $groupOptions, string $fingerprint, int $page, int $perPage): string
    {
        $query = $this->buildBaseQuery($filters, $groupOptions, $perPage);
        if ($fingerprint !== '') {
            $query['fingerprint'] = $fingerprint;
        }
        if ($page > 1) {
            $query['page'] = (string) $page;
        }

        return base_url('admin/telemetry' . ($query !== [] ? '?' . http_build_query($query) : ''));
    }
}