<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * StorePointsModel
 *
 * Manages point transactions per user.
 * Points can be positive (earned) or negative (spent).
 *
 * Android API usage:
 *   POST /api/store-points → Body: {"points": 100, "reason": "watch_reward"}
 *   GET  /api/store-points → Returns history + total
 */
class StorePointsModel extends Model
{
    protected $table      = 'store_points';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'points',
        'reason',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Add a point transaction for a user
     *
     * @param int    $userId
     * @param int    $points  Positive to add, negative to deduct
     * @param string $reason  Description of why points were given/spent
     */
    public function addPoints(int $userId, int $points, string $reason = ''): void
    {
        $this->insert([
            'user_id'    => $userId,
            'points'     => $points,
            'reason'     => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get total points balance for a user
     */
    public function getTotalPoints(int $userId): int
    {
        $result = $this->db->table('store_points')
            ->selectSum('points')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        return (int) ($result['points'] ?? 0);
    }

    /**
     * Get full transaction history for a user
     */
    public function getHistory(int $userId, int $limit = 100): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get total points distributed to all users (admin dashboard)
     */
    public function getTotalPointsDistributed(): int
    {
        $result = $this->db->table('store_points')
            ->selectSum('points')
            ->where('points >', 0)
            ->get()
            ->getRowArray();

        return (int) ($result['points'] ?? 0);
    }
}
