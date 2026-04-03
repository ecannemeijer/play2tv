<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixDatabaseSessionSchema extends Migration
{
    public function up(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        if (! $this->hasTable('ci_sessions')) {
            return;
        }

        $timestampType = $this->getColumnType('ci_sessions', 'timestamp');

        if ($timestampType !== null && $timestampType !== 'TIMESTAMP' && $timestampType !== 'DATETIME') {
            $this->db->query('ALTER TABLE `ci_sessions` MODIFY `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }

        if (! $this->hasIndexOnColumn('ci_sessions', 'timestamp')) {
            $this->db->query('ALTER TABLE `ci_sessions` ADD INDEX `ci_sessions_timestamp_idx` (`timestamp`)');
        }
    }

    public function down(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        if (! $this->hasTable('ci_sessions')) {
            return;
        }

        $this->db->query('ALTER TABLE `ci_sessions` MODIFY `timestamp` INT UNSIGNED NOT NULL DEFAULT 0');
    }

    private function hasTable(string $table): bool
    {
        $result = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getResultArray();

        return $result !== [];
    }

    private function getColumnType(string $table, string $column): ?string
    {
        $sql = 'SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1';
        $row = $this->db->query($sql, [$table, $column])->getRowArray();

        return isset($row['DATA_TYPE']) ? strtoupper((string) $row['DATA_TYPE']) : null;
    }

    private function hasIndexOnColumn(string $table, string $column): bool
    {
        $sql = 'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1';
        $row = $this->db->query($sql, [$table, $column])->getRowArray();

        return $row !== null;
    }
}