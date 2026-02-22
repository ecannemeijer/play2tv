<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserIpsLogModel
 *
 * Audit log for every login attempt, capturing IP and user agent.
 */
class UserIpsLogModel extends Model
{
    protected $table      = 'user_ips_log';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Log a login event
     */
    public function log(int $userId, string $ip, string $userAgent = ''): void
    {
        $this->insert([
            'user_id'    => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get all IP logs for a user (admin view)
     */
    public function getLogsForUser(int $userId): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get active users in last 24 hours (admin dashboard)
     */
    public function getActiveUsersLast24h(): int
    {
        return (int) $this->db->table('user_ips_log')
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->countAllResults();
    }
}
