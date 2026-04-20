<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class TelemetryEventModel extends Model
{
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

        return [
            'total' => $total,
            'last24h' => $last24h,
            'crashes24h' => $crashes24h,
            'manualReports24h' => $manualReports24h,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyFilters($builder, array $filters): void
    {
        if (! empty($filters['type'])) {
            $builder->where('te.event_type', (string) $filters['type']);
        }

        if (! empty($filters['severity'])) {
            $builder->where('te.severity', (string) $filters['severity']);
        }

        if (! empty($filters['app_version'])) {
            $builder->where('te.app_version', (string) $filters['app_version']);
        }

        if (! empty($filters['query'])) {
            $query = trim((string) $filters['query']);
            $builder->groupStart()
                ->like('te.event_type', $query)
                ->orLike('te.channel_name', $query)
                ->orLike('te.last_action', $query)
                ->orLike('te.device_name', $query)
                ->orLike('te.data_json', $query)
                ->groupEnd();
        }
    }
}