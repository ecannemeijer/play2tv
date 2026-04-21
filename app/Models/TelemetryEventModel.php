<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class TelemetryEventModel extends Model
{
    private const EXPORT_LIMIT = 5000;
    private const FINGERPRINT_EVENT_LIMIT = 120;

    protected $table = 'telemetry_events';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'event_type',
        'severity',
        'app_version',
        'app_code',
        'device_name',
        'android_version',
        'channel_name',
        'last_action',
        'stream_type',
        'client_timestamp',
        'ip_hash',
        'fingerprint_hash',
        'data_json',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * @param array<string, mixed> $filters
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    public function getPaged(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $builder = $this->db->table($this->table . ' te');
        $this->applyFilters($builder, $filters);

        $total = (int) $builder->countAllResults(false);
        $rows = $builder
            ->orderBy('te.created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        return [
            'rows' => $rows,
            'total' => $total,
        ];
    }

    public function getOverview(): array
    {
        $table = $this->db->table($this->table);
        $total = (int) $table->countAllResults();
        $uniqueFingerprints = (int) $this->buildGroupedFingerprintBuilder()->get()->getNumRows();
        $latestRow = $this->db->table($this->table)
            ->select('created_at')
            ->orderBy('created_at', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        $last24h = (int) $this->db->table($this->table)
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->countAllResults();

        $crashes24h = (int) $this->db->table($this->table)
            ->groupStart()
                ->where('severity', 'error')
                ->orLike('event_type', 'crash')
                ->orLike('event_type', 'error')
            ->groupEnd()
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->countAllResults();

        $manualReports24h = (int) $this->db->table($this->table)
            ->where('event_type', 'manual_report')
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->countAllResults();

        $topTypes = $this->db->table($this->table)
            ->select('event_type, COUNT(*) AS total')
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->groupBy('event_type')
            ->orderBy('total', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        return [
            'total' => $total,
            'uniqueFingerprints' => $uniqueFingerprints,
            'last24h' => $last24h,
            'crashes24h' => $crashes24h,
            'manualReports24h' => $manualReports24h,
            'latestCreatedAt' => $latestRow['created_at'] ?? null,
            'topTypes24h' => $topTypes,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function getFingerprintGroups(int $page, int $perPage, array $filters = [], array $groupOptions = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $total = $this->buildGroupedFingerprintBuilder($filters, $groupOptions)->get()->getNumRows();
        $rows = $this->applyGroupedSort(
            $this->buildGroupedFingerprintBuilder($filters, $groupOptions),
            (string) ($groupOptions['sort'] ?? 'latest')
        )
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        return [
            'rows' => $rows,
            'total' => $total,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>|null
     */
    public function getFingerprintGroupSummary(string $fingerprintKey, array $filters = []): ?array
    {
        $builder = $this->buildGroupedFingerprintBuilder($filters);
        $this->applyFingerprintScope($builder, $fingerprintKey);

        return $builder->get()->getRowArray();
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function getFingerprintEvents(string $fingerprintKey, array $filters = [], int $limit = self::FINGERPRINT_EVENT_LIMIT): array
    {
        $builder = $this->db->table($this->table . ' te');
        $this->applyFilters($builder, $filters);
        $this->applyFingerprintScope($builder, $fingerprintKey);

        return $builder
            ->orderBy('te.created_at', 'DESC')
            ->limit(max(1, min(self::FINGERPRINT_EVENT_LIMIT, $limit)))
            ->get()
            ->getResultArray();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countFiltered(array $filters = [], ?string $fingerprintKey = null): int
    {
        $builder = $this->db->table($this->table . ' te');
        $this->applyFilters($builder, $filters);
        if ($fingerprintKey !== null && $fingerprintKey !== '') {
            $this->applyFingerprintScope($builder, $fingerprintKey);
        }

        return (int) $builder->countAllResults();
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function getExportRows(array $filters = [], int $limit = self::EXPORT_LIMIT, ?string $fingerprintKey = null): array
    {
        $builder = $this->db->table($this->table . ' te');
        $this->applyFilters($builder, $filters);
        if ($fingerprintKey !== null && $fingerprintKey !== '') {
            $this->applyFingerprintScope($builder, $fingerprintKey);
        }

        return $builder
            ->orderBy('te.created_at', 'DESC')
            ->limit(max(1, min(self::EXPORT_LIMIT, $limit)))
            ->get()
            ->getResultArray();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function deleteByFilters(array $filters = [], ?string $fingerprintKey = null): int
    {
        $builder = $this->db->table($this->table);
        $this->applyFilters($builder, $filters, '');
        if ($fingerprintKey !== null && $fingerprintKey !== '') {
            $this->applyFingerprintScope($builder, $fingerprintKey, 'fingerprint_hash');
        }
        $builder->delete();

        return $this->db->affectedRows();
    }

    public function pruneOlderThanDays(int $days): int
    {
        $days = max(1, $days);
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

        $this->db->table($this->table)
            ->where('created_at <', $cutoff)
            ->delete();

        return $this->db->affectedRows();
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyFilters($builder, array $filters, string $tableAlias = 'te'): void
    {
        $column = static fn (string $field): string => $tableAlias !== '' ? $tableAlias . '.' . $field : $field;

        if (! empty($filters['type'])) {
            $builder->where($column('event_type'), (string) $filters['type']);
        }

        if (! empty($filters['severity'])) {
            $builder->where($column('severity'), (string) $filters['severity']);
        }

        if (! empty($filters['app_version'])) {
            $builder->where($column('app_version'), (string) $filters['app_version']);
        }

        if (! empty($filters['query'])) {
            $query = trim((string) $filters['query']);
            $builder->groupStart()
                ->like($column('event_type'), $query)
                ->orLike($column('channel_name'), $query)
                ->orLike($column('last_action'), $query)
                ->orLike($column('device_name'), $query)
                ->orLike($column('data_json'), $query)
                ->groupEnd();
        }
    }

    private function buildGroupedFingerprintBuilder(array $filters = [], array $groupOptions = [])
    {
        $builder = $this->db->table($this->table . ' te');
        $this->applyFilters($builder, $filters);
        $fingerprintExpr = $this->fingerprintKeyExpression();

        $builder = $builder
            ->select($fingerprintExpr . ' AS fingerprint_key', false)
            ->select('COUNT(*) AS total_events', false)
            ->select('SUM(CASE WHEN te.severity = "error" THEN 1 ELSE 0 END) AS error_events', false)
            ->select('SUM(CASE WHEN te.severity = "warning" THEN 1 ELSE 0 END) AS warning_events', false)
            ->select('MAX(te.created_at) AS latest_created_at', false)
            ->select('MIN(te.created_at) AS first_created_at', false)
            ->select('COUNT(DISTINCT te.event_type) AS unique_event_types', false)
            ->select('COUNT(DISTINCT te.channel_name) AS unique_channels', false)
            ->select('MAX(NULLIF(te.device_name, "")) AS sample_device_name', false)
            ->select('MAX(NULLIF(te.app_version, "")) AS sample_app_version', false)
            ->select('GROUP_CONCAT(DISTINCT te.event_type ORDER BY te.event_type SEPARATOR ",") AS event_types_csv', false)
            ->groupBy($fingerprintExpr, false);

        $this->applyGroupFilters($builder, $groupOptions);

        return $builder;
    }

    private function applyGroupFilters($builder, array $groupOptions): void
    {
        $groupQuery = trim((string) ($groupOptions['group_query'] ?? ''));
        if ($groupQuery === '') {
            return;
        }

        $escapedLike = $this->db->escapeLikeString($groupQuery);
        $likeValue = $this->db->escape('%' . $escapedLike . '%');

        $builder->having(
            sprintf(
                '(fingerprint_key LIKE %1$s ESCAPE "!" OR sample_device_name LIKE %1$s ESCAPE "!" OR sample_app_version LIKE %1$s ESCAPE "!" OR event_types_csv LIKE %1$s ESCAPE "!")',
                $likeValue
            ),
            null,
            false
        );
    }

    private function applyGroupedSort($builder, string $sort)
    {
        return match ($sort) {
            'errors' => $builder->orderBy('error_events', 'DESC')->orderBy('latest_created_at', 'DESC'),
            'events' => $builder->orderBy('total_events', 'DESC')->orderBy('latest_created_at', 'DESC'),
            'device' => $builder->orderBy('sample_device_name', 'ASC')->orderBy('latest_created_at', 'DESC'),
            'fingerprint' => $builder->orderBy('fingerprint_key', 'ASC')->orderBy('latest_created_at', 'DESC'),
            default => $builder->orderBy('latest_created_at', 'DESC')->orderBy('error_events', 'DESC'),
        };
    }

    private function applyFingerprintScope($builder, string $fingerprintKey, string $fingerprintColumn = 'te.fingerprint_hash'): void
    {
        if ($this->isUnknownFingerprintKey($fingerprintKey)) {
            $builder->groupStart()
                ->where($fingerprintColumn . ' IS NULL', null, false)
                ->orWhere($fingerprintColumn, '')
                ->groupEnd();

            return;
        }

        $builder->where($fingerprintColumn, $fingerprintKey);
    }

    private function fingerprintKeyExpression(): string
    {
        return "COALESCE(NULLIF(te.fingerprint_hash, ''), 'unknown')";
    }

    private function isUnknownFingerprintKey(string $fingerprintKey): bool
    {
        return $fingerprintKey === '' || $fingerprintKey === 'unknown';
    }
}