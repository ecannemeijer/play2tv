<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\UserModel;
use App\Models\WatchHistoryModel;
use App\Models\StorePointsModel;
use App\Models\UserIpsLogModel;
use CodeIgniter\Controller;

/**
 * DashboardController
 *
 * Admin dashboard showing platform statistics.
 * All statistics used by Chart.js charts on the dashboard.
 *
 * Route: GET /admin/dashboard (AdminAuthFilter required)
 */
class DashboardController extends Controller
{
    private UserModel         $userModel;
    private WatchHistoryModel $historyModel;
    private StorePointsModel  $pointsModel;
    private UserIpsLogModel   $ipsModel;

    public function __construct()
    {
        $this->userModel   = new UserModel();
        $this->historyModel = new WatchHistoryModel();
        $this->pointsModel  = new StorePointsModel();
        $this->ipsModel     = new UserIpsLogModel();
        helper(['url', 'form']);
    }

    public function index()
    {
        $db = \Config\Database::connect();

        // ── Total users ───────────────────────────────────────────────────────
        $totalUsers = $this->userModel->countAllResults();

        // ── Active users last 24h (unique user IDs in ip log) ────────────────
        $activeUsers24h = (int) $db->table('user_ips_log')
            ->distinct()
            ->select('user_id')
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->countAllResults();

        // ── Premium users ─────────────────────────────────────────────────────
        $premiumUsers = $this->userModel
            ->where('premium', 1)
            ->where('(premium_until IS NULL OR premium_until > NOW())')
            ->countAllResults();

        // ── Total watch events ────────────────────────────────────────────────
        $totalWatchEvents = $this->historyModel->getTotalWatchEvents();

        // ── Most watched content (top 10) ─────────────────────────────────────
        $mostWatched = $this->historyModel->getMostWatched(10);

        // ── Total store points distributed ────────────────────────────────────
        $totalPoints = $this->pointsModel->getTotalPointsDistributed();

        // ── New registrations per day last 7 days (for Chart.js) ─────────────
        $registrations = $db->table('users')
            ->select("DATE(created_at) AS day, COUNT(*) AS count")
            ->where('created_at >=', date('Y-m-d', strtotime('-7 days')))
            ->groupBy("DATE(created_at)")
            ->orderBy('day', 'ASC')
            ->get()
            ->getResultArray();

        // Fill missing days with 0
        $chartDays   = [];
        $chartCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $day     = date('Y-m-d', strtotime("-{$i} days"));
            $dayLabel = date('d M', strtotime($day));
            $chartDays[] = $dayLabel;
            $found = array_filter($registrations, fn($r) => $r['day'] === $day);
            $chartCounts[] = $found ? (int) reset($found)['count'] : 0;
        }

        // ── Watch events per day last 7 days (for Chart.js) ──────────────────
        $watchPerDay = $db->table('watch_history')
            ->select("DATE(watched_at) AS day, COUNT(*) AS count")
            ->where('watched_at >=', date('Y-m-d', strtotime('-7 days')))
            ->groupBy("DATE(watched_at)")
            ->orderBy('day', 'ASC')
            ->get()
            ->getResultArray();

        $watchCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $day     = date('Y-m-d', strtotime("-{$i} days"));
            $found = array_filter($watchPerDay, fn($r) => $r['day'] === $day);
            $watchCounts[] = $found ? (int) reset($found)['count'] : 0;
        }

        return view('admin/dashboard', [
            'title'            => 'Dashboard — Play2TV Admin',
            'totalUsers'       => $totalUsers,
            'activeUsers24h'   => $activeUsers24h,
            'premiumUsers'     => $premiumUsers,
            'totalWatchEvents' => $totalWatchEvents,
            'mostWatched'      => $mostWatched,
            'totalPoints'      => $totalPoints,
            'chartDays'        => json_encode($chartDays),
            'chartRegCounts'   => json_encode($chartCounts),
            'chartWatchCounts' => json_encode($watchCounts),
        ]);
    }
}
