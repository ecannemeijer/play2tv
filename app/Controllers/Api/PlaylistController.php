<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\PlaylistModel;
use App\Models\UserModel;

/**
 * PlaylistController
 *
 * Serves the M3U playlist to premium users.
 *
 * Endpoints:
 *   GET /api/playlist → Returns M3U content (premium only, JWT required)
 *
 * Non-premium users receive HTTP 403.
 * Active playlist is set by admin in the admin panel.
 *
 * Android integration:
 *   The app can call this endpoint to dynamically fetch an admin-managed playlist
 *   instead of requiring users to manually add playlists.
 *
 *   // In IptvRepository.kt:
 *   @GET("api/playlist")
 *   suspend fun getAdminPlaylist(
 *     @Header("Authorization") token: String
 *   ): Response<ResponseBody>  // Returns raw M3U text
 *
 * Response Content-Type: text/plain (M3U format)
 * Example M3U:
 *   #EXTM3U
 *   #EXTINF:-1 tvg-id="channel1" tvg-name="Channel 1",Channel 1
 *   http://stream.example.com/live/user/pass/1.ts
 */
class PlaylistController extends BaseApiController
{
    private PlaylistModel $playlistModel;
    private UserModel     $userModel;

    public function __construct()
    {
        $this->playlistModel = new PlaylistModel();
        $this->userModel     = new UserModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/playlist
    // Header: Authorization: Bearer {token}
    //
    // Response 200 (premium user):
    //   Content-Type: text/plain
    //   #EXTM3U
    //   ...
    //
    // Response 403 (non-premium):
    //   { "success": false, "message": "Premium abonnement vereist." }
    //
    // Response 404 (no active playlist):
    //   { "success": false, "message": "Geen actieve playlist beschikbaar." }
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $userId = $this->getAuthUserId();
        $user   = $this->userModel->find($userId);

        if (! $user) {
            return $this->error('Gebruiker niet gevonden.', 404);
        }

        // Check premium status (re-verify from DB, not just JWT, for accuracy)
        if (! $this->userModel->isPremium($user)) {
            return $this->error(
                'Premium abonnement vereist voor toegang tot de playlist.',
                403
            );
        }

        $playlist = $this->playlistModel->getActivePlaylist();

        if (! $playlist || empty($playlist['m3u_content'])) {
            return $this->error('Geen actieve playlist beschikbaar.', 404);
        }

        // Return raw M3U content as plain text
        return $this->response
            ->setStatusCode(200)
            ->setContentType('text/plain; charset=utf-8')
            ->setBody($playlist['m3u_content']);
    }
}
