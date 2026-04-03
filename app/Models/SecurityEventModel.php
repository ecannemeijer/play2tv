<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class SecurityEventModel extends Model
{
    protected $table      = 'security_events';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'event_type',
        'severity',
        'ip_hash',
        'fingerprint_hash',
        'route',
        'details',
        'created_at',
    ];

    protected $useTimestamps = false;

    public function getRecentWithUsers(int $limit = 50, array $filters = []): array
    {
        $builder = $this->db->table($this->table . ' se')
            ->select('se.*, u.email')
            ->join('users u', 'u.id = se.user_id', 'left')
            ->orderBy('se.created_at', 'DESC')
            ->limit(max(1, $limit));

        if (! empty($filters['user_id'])) {
            $builder->where('se.user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['severity'])) {
            $builder->where('se.severity', (string) $filters['severity']);
        }

        if (! empty($filters['query'])) {
            $query = trim((string) $filters['query']);
            $builder->groupStart()
                ->like('se.event_type', $query)
                ->orLike('se.route', $query)
                ->orLike('u.email', $query)
                ->groupEnd();
        }

        if (! empty($filters['suspicious_only'])) {
            $builder->groupStart()
                ->whereIn('se.severity', ['error', 'critical', 'alert'])
                ->orLike('se.event_type', 'failed')
                ->orLike('se.event_type', 'invalid')
                ->orLike('se.event_type', 'mismatch')
                ->orLike('se.event_type', 'reuse')
                ->orLike('se.event_type', 'denied')
                ->orLike('se.event_type', 'rate_limit')
                ->groupEnd();
        }

        return $builder->get()->getResultArray();
    }
}