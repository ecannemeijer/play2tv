<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * WatchHistoryModel
 *
 * Tracks watch progress for movies and series episodes per user.
 *
 * Android API usage:
 *   POST /api/history → Body:
 *     {
 *       "content_type": "movie",
 *       "content_id": "12345",
 *       "season": null,
 *       "episode": null,
 *       "progress_seconds": 3600
 *     }
 *   GET /api/history → Returns last 50 items
 */
class WatchHistoryModel extends Model
{
    protected $table      = 'watch_history';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'content_type',
        'content_id',
        'season',
        'episode',
        'progress_seconds',
        'watched_at',
    ];

    protected $useTimestamps = false;

    /**
     * Upsert watch progress
     *
     * Updates if record already exists for (user_id, content_type, content_id, season, episode),
     * inserts otherwise.
     */
    public function upsertHistory(int $userId, array $data): void
    {
        $query = $this->where('user_id', $userId)
                      ->where('content_type', $data['content_type'])
                      ->where('content_id', $data['content_id']);

        if (! empty($data['season'])) {
            $query->where('season', $data['season']);
        }
        if (! empty($data['episode'])) {
            $query->where('episode', $data['episode']);
        }

        $existing = $query->first();
        $now      = date('Y-m-d H:i:s');

        if ($existing) {
            $this->update($existing['id'], [
                'progress_seconds' => $data['progress_seconds'] ?? $existing['progress_seconds'],
                'watched_at'       => $now,
            ]);
        } else {
            $this->insert(array_merge($data, [
                'user_id'    => $userId,
                'watched_at' => $now,
            ]));
        }
    }

    /**
     * Get watch history for a user, most recent first
     */
    public function getHistory(int $userId, int $limit = 50): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('watched_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get total watch event count (admin dashboard)
     */
    public function getTotalWatchEvents(): int
    {
        return $this->countAllResults();
    }

    /**
     * Get most watched content (admin dashboard)
     *
     * @return array Top 10 most watched content items
     */
    public function getMostWatched(int $limit = 10): array
    {
        return $this->db->table('watch_history')
            ->select('content_type, content_id, COUNT(*) as watch_count')
            ->groupBy(['content_type', 'content_id'])
            ->orderBy('watch_count', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}
