<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\StorePointsModel;

/**
 * StorePointsController
 *
 * Manages the in-app points (reward) system for Play2TV users.
 *
 * Endpoints:
 *   POST /api/store-points → Add or deduct points (JWT required)
 *   GET  /api/store-points → Get balance + history (JWT required)
 *
 * Points use cases:
 *   - Watch reward: earn points for watching content
 *   - Premium purchase: spend points for premium days
 *   - Bonus: admin grants bonus points
 *
 * Android example:
 *   @POST("api/store-points")
 *   suspend fun addPoints(
 *     @Header("Authorization") token: String,
 *     @Body body: StorePointsRequest
 *   ): Response<ApiResponse>
 *
 *   data class StorePointsRequest(val points: Int, val reason: String)
 *   // points: positive to add, negative to spend
 */
class StorePointsController extends BaseApiController
{
    private StorePointsModel $pointsModel;

    public function __construct()
    {
        $this->pointsModel = new StorePointsModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/store-points
    // Header: Authorization: Bearer {token}
    // Body (JSON):
    //   { "points": 100, "reason": "watch_reward" }
    //
    // Response 200:
    //   {
    //     "success": true,
    //     "data": { "new_total": 350 }
    //   }
    // ─────────────────────────────────────────────────────────────────────────
    public function add()
    {
        $userId = $this->getAuthUserId();
        $body   = $this->request->getJSON(true) ?? [];

        if (! isset($body['points']) || ! is_numeric($body['points'])) {
            return $this->error("Veld 'points' is verplicht en moet numeriek zijn.", 422);
        }

        $points = (int) $body['points'];

        if ($points === 0) {
            return $this->error("Punten mogen niet 0 zijn.", 422);
        }

        $reason = htmlspecialchars(strip_tags($body['reason'] ?? ''));

        // Check if user has enough points to spend
        if ($points < 0) {
            $current = $this->pointsModel->getTotalPoints($userId);
            if (($current + $points) < 0) {
                return $this->error('Onvoldoende punten. Huidig saldo: ' . $current, 400);
            }
        }

        $this->pointsModel->addPoints($userId, $points, $reason);
        $newTotal = $this->pointsModel->getTotalPoints($userId);

        return $this->ok(['new_total' => $newTotal], 'Punten bijgewerkt.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/store-points
    // Header: Authorization: Bearer {token}
    //
    // Response 200:
    //   {
    //     "success": true,
    //     "data": {
    //       "total": 350,
    //       "history": [
    //         {"id":1,"points":100,"reason":"watch_reward","created_at":"2024-01-01 12:00:00"},
    //         {"id":2,"points":250,"reason":"bonus","created_at":"2024-01-02 09:00:00"}
    //       ]
    //     }
    //   }
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $userId  = $this->getAuthUserId();
        $total   = $this->pointsModel->getTotalPoints($userId);
        $history = $this->pointsModel->getHistory($userId);

        return $this->ok([
            'total'   => $total,
            'history' => $history,
        ]);
    }
}
