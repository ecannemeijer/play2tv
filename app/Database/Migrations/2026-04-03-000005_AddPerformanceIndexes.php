<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerformanceIndexes extends Migration
{
    public function up(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        $this->addIndexIfMissing('watch_history', 'idx_watch_history_user_watched_at', ['user_id', 'watched_at']);
        $this->addIndexIfMissing('watch_history', 'idx_watch_history_content', ['content_type', 'content_id']);
        $this->addIndexIfMissing('user_ips_log', 'idx_user_ips_log_created_at', ['created_at']);
        $this->addIndexIfMissing('user_ips_log', 'idx_user_ips_log_user_created_at', ['user_id', 'created_at']);
        $this->addIndexIfMissing('playlists', 'idx_playlists_active_updated', ['is_active', 'updated_at']);
        $this->addIndexIfMissing('auth_refresh_tokens', 'idx_refresh_tokens_revoked_expires', ['revoked_at', 'expires_at']);
        $this->addIndexIfMissing('security_events', 'idx_security_events_created_at', ['created_at']);
        $this->addIndexIfMissing('security_events', 'idx_security_events_route_created_at', ['route', 'created_at']);
    }

    public function down(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        $this->dropIndexIfExists('watch_history', 'idx_watch_history_user_watched_at');
        $this->dropIndexIfExists('watch_history', 'idx_watch_history_content');
        $this->dropIndexIfExists('user_ips_log', 'idx_user_ips_log_created_at');
        $this->dropIndexIfExists('user_ips_log', 'idx_user_ips_log_user_created_at');
        $this->dropIndexIfExists('playlists', 'idx_playlists_active_updated');
        $this->dropIndexIfExists('auth_refresh_tokens', 'idx_refresh_tokens_revoked_expires');
        $this->dropIndexIfExists('security_events', 'idx_security_events_created_at');
        $this->dropIndexIfExists('security_events', 'idx_security_events_route_created_at');
    }

    /**
     * @param list<string> $columns
     */
    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (! $this->tableExists($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        $quotedColumns = implode(', ', array_map(static fn(string $column): string => '`' . $column . '`', $columns));
        $this->db->query('ALTER TABLE `' . $table . '` ADD INDEX `' . $indexName . '` (' . $quotedColumns . ')');
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->tableExists($table) || ! $this->indexExists($table, $indexName)) {
            return;
        }

        $this->db->query('ALTER TABLE `' . $table . '` DROP INDEX `' . $indexName . '`');
    }

    private function tableExists(string $table): bool
    {
        return $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getResultArray() !== [];
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $sql = 'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1';
        return $this->db->query($sql, [$table, $indexName])->getRowArray() !== null;
    }
}