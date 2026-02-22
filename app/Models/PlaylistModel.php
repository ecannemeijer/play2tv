<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * PlaylistModel
 *
 * Manages M3U playlists uploaded by admin.
 * Premium users can retrieve the active playlist via GET /api/playlist.
 *
 * Android API usage:
 *   GET /api/playlist → Returns full M3U content (text/plain)
 *   Non-premium users receive HTTP 403
 */
class PlaylistModel extends Model
{
    protected $table      = 'playlists';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'name',
        'm3u_content',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get the currently active playlist
     */
    public function getActivePlaylist(): ?array
    {
        return $this->where('is_active', 1)
                    ->orderBy('created_at', 'DESC')
                    ->first();
    }

    /**
     * Deactivate all playlists, then activate given one.
     * Only one playlist should be active at a time.
     */
    public function setActivePlaylist(int $playlistId): void
    {
        $this->db->table('playlists')->update(['is_active' => 0]);
        $this->update($playlistId, ['is_active' => 1]);
    }
}
