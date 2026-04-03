<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Performance;

class PruneOperationalData extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'maintenance:prune-data';
    protected $description = 'Prune expired or stale operational records from high-growth tables.';
    protected $usage       = 'maintenance:prune-data [--dry-run]';
    protected $options     = [
        '--dry-run' => 'Show how many rows would be deleted without executing the deletes.',
    ];

    public function run(array $params): void
    {
        $dryRun = CLI::getOption('dry-run') !== null;
        $db     = db_connect();
        $config = config(Performance::class);

        CLI::write('Pruning operational data' . ($dryRun ? ' (dry-run)' : '') . '...', 'yellow');

        $this->pruneTable(
            $db,
            'auth_refresh_tokens',
            'expires_at <',
            date('Y-m-d H:i:s', strtotime('-' . max(1, (int) $config->retentionDays['refresh_tokens']) . ' days')),
            $dryRun,
            'Expired refresh tokens'
        );

        $this->pruneTable(
            $db,
            'security_events',
            'created_at <',
            date('Y-m-d H:i:s', strtotime('-' . max(1, (int) $config->retentionDays['security_events']) . ' days')),
            $dryRun,
            'Old security events'
        );

        $this->pruneTable(
            $db,
            'user_ips_log',
            'created_at <',
            date('Y-m-d H:i:s', strtotime('-' . max(1, (int) $config->retentionDays['ip_logs']) . ' days')),
            $dryRun,
            'Old IP audit logs'
        );

        $this->pruneTable(
            $db,
            'watch_history',
            'watched_at <',
            date('Y-m-d H:i:s', strtotime('-' . max(1, (int) $config->retentionDays['watch_history']) . ' days')),
            $dryRun,
            'Old watch history rows'
        );

        if ($db->tableExists('ci_sessions')) {
            $builder = $db->table('ci_sessions');
            $ttlDays = max(1, (int) $config->retentionDays['sessions']);
            $cutoff  = date('Y-m-d H:i:s', strtotime('-' . $ttlDays . ' days'));

            $count = $builder->where('timestamp <', $cutoff)->countAllResults(false);
            if ($dryRun === false && $count > 0) {
                $builder->delete();
            }

            CLI::write('Stale sessions: ' . $count . ($dryRun ? ' (would delete)' : ' deleted'), $count > 0 ? 'green' : 'white');
        }
    }

    private function pruneTable(
        \CodeIgniter\Database\BaseConnection $db,
        string $table,
        string $condition,
        string $value,
        bool $dryRun,
        string $label
    ): void {
        if (! $db->tableExists($table)) {
            CLI::write($label . ': skipped (table missing)', 'dark_gray');
            return;
        }

        $builder = $db->table($table);
        $count   = $builder->where($condition, $value)->countAllResults(false);

        if ($dryRun === false && $count > 0) {
            $builder->delete();
        }

        CLI::write($label . ': ' . $count . ($dryRun ? ' (would delete)' : ' deleted'), $count > 0 ? 'green' : 'white');
    }
}