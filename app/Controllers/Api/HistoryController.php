<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\WatchHistoryModel;

/**
 * HistoryController
 *
 * Manages watch history for the Play2TV Android app.
 * Supports movies and series episodes with progress tracking.
 *
 * Endpoints:
 *   POST /api/history → Record/update watch progress (JWT required)
 *   GET  /api/history → Get watch history (JWT required)
 *
 * Android example:
 *   // When user pauses or stops playback:
 *   @POST("api/history")
 *   suspend fun saveHistory(
 *     @Header("Authorization") token: String,
 *     @Body body: WatchHistoryRequest
 *   ): Response<ApiResponse>
 *
 *   data class WatchHistoryRequest(
 *     val content_type: String,   // "movie" or "series"
 *     val content_id: String,     // Xtream stream ID
 *     val season: Int?,           // null for movies
 *     val episode: Int?,          // null for movies
 *     val progress_seconds: Int   // WatchProgress.position / 1000
 *   )
 *
 * This maps to the Android Room entity WatchProgress:
 *   contentId, contentType, position (ms), duration (ms), lastWatched
 */
class HistoryController extends BaseApiController
{
    private WatchHistoryModel $historyModel;

    public function __construct()
    {
        $this->historyModel = new WatchHistoryModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/history
    // Header: Authorization: Bearer {token}
    // Body (JSON):
    //   {
    //     "content_type": "movie",       // "movie" or "series"
    //     "content_id": "12345",
    //     "season": null,
    //     "episode": null,
    //     "progress_seconds": 3600
    //   }
    //
    // Response 200:
    //   { "success": true, "message": "Geschiedenis opgeslagen." }
    // ─────────────────────────────────────────────────────────────────────────
    public function save()
    {
        $userId = $this->getAuthUserId();
        $body   = $this->getJsonBody(['content_type', 'content_id']);

        if ($body === false) {
            return $this->error('Ongeldige geschiedenispayload.', 422, $this->getValidationErrors());
        }

        if (! $this->validatePayload($body, [
            'content_type'     => 'required|in_list[movie,series]',
            'content_id'       => 'required|max_length[100]',
            'season'           => 'permit_empty|integer|greater_than_equal_to[1]',
            'episode'          => 'permit_empty|integer|greater_than_equal_to[1]',
            'progress_seconds' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[86400]',
        ])) {
            return $this->error('Geschiedenisvalidatie mislukt.', 422, $this->getValidationErrors());
        }

        $contentType = (string) $body['content_type'];
        $data = [
            'content_type'     => $contentType,
            'content_id'       => $this->sanitizeText((string) $body['content_id'], 100),
            'season'           => isset($body['season']) ? (int) $body['season'] : null,
            'episode'          => isset($body['episode']) ? (int) $body['episode'] : null,
            'progress_seconds' => isset($body['progress_seconds']) ? (int) $body['progress_seconds'] : 0,
        ];

        $this->historyModel->upsertHistory($userId, $data);

        return $this->ok([], 'Geschiedenis opgeslagen.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/history
    // Header: Authorization: Bearer {token}
    // Query: ?limit=50 (optional)
    //
    // Response 200:
    //   {
    //     "success": true,
    //     "data": [
    //       {
    //         "id": 1,
    //         "content_type": "movie",
    //         "content_id": "12345",
    //         "season": null,
    //         "episode": null,
    //         "progress_seconds": 3600,
    //         "watched_at": "2024-01-15 20:30:00"
    //       }
    //     ]
    //   }
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $userId = $this->getAuthUserId();
        $limit  = (int) ($this->request->getGet('limit') ?? 50);
        $limit  = min(max($limit, 1), 200);

        $history = $this->historyModel->getHistory($userId, $limit);

        return $this->ok($history);
    }
}
