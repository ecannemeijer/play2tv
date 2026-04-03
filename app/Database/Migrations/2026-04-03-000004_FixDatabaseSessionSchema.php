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
            $this->convertLegacyTimestampColumn();
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

        $timestampType = $this->getColumnType('ci_sessions', 'timestamp');

        if ($timestampType === 'TIMESTAMP' || $timestampType === 'DATETIME') {
            $this->db->query('ALTER TABLE `ci_sessions` ADD COLUMN `timestamp_int` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `ip_address`');
            $this->db->query('UPDATE `ci_sessions` SET `timestamp_int` = UNIX_TIMESTAMP(`timestamp`)');

            if ($this->hasIndexByName('ci_sessions', 'ci_sessions_timestamp_idx')) {
                $this->db->query('ALTER TABLE `ci_sessions` DROP INDEX `ci_sessions_timestamp_idx`');
            }

            $this->db->query('ALTER TABLE `ci_sessions` DROP COLUMN `timestamp`');
            $this->db->query('ALTER TABLE `ci_sessions` CHANGE `timestamp_int` `timestamp` INT UNSIGNED NOT NULL DEFAULT 0');
            $this->db->query('ALTER TABLE `ci_sessions` ADD INDEX `ci_sessions_timestamp_idx` (`timestamp`)');
        }
    }

    private function convertLegacyTimestampColumn(): void
    {
        if (! $this->hasColumn('ci_sessions', 'timestamp_tmp')) {
            $this->db->query('ALTER TABLE `ci_sessions` ADD COLUMN `timestamp_tmp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `ip_address`');
        }

        $this->db->query(
            'UPDATE `ci_sessions`
             SET `timestamp_tmp` = CASE
                 WHEN `timestamp` IS NULL THEN CURRENT_TIMESTAMP
                 WHEN `timestamp` BETWEEN 1 AND 2147483647 THEN FROM_UNIXTIME(`timestamp`)
                 ELSE CURRENT_TIMESTAMP
             END'
        );

        if ($this->hasIndexOnColumn('ci_sessions', 'timestamp')) {
            foreach ($this->getIndexesForColumn('ci_sessions', 'timestamp') as $indexName) {
                $this->db->query('ALTER TABLE `ci_sessions` DROP INDEX `' . str_replace('`', '``', $indexName) . '`');
            }
        }

        $this->db->query('ALTER TABLE `ci_sessions` DROP COLUMN `timestamp`');
        $this->db->query('ALTER TABLE `ci_sessions` CHANGE `timestamp_tmp` `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->db->query('ALTER TABLE `ci_sessions` ADD INDEX `ci_sessions_timestamp_idx` (`timestamp`)');
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

    private function hasColumn(string $table, string $column): bool
    {
        return $this->getColumnType($table, $column) !== null;
    }

    private function hasIndexOnColumn(string $table, string $column): bool
    {
        $sql = 'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1';
        $row = $this->db->query($sql, [$table, $column])->getRowArray();

        return $row !== null;
    }

    /**
     * @return list<string>
     */
    private function getIndexesForColumn(string $table, string $column): array
    {
        $sql = 'SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?';
        $rows = $this->db->query($sql, [$table, $column])->getResultArray();

        return array_values(array_map(static fn(array $row): string => (string) $row['INDEX_NAME'], $rows));
    }

    private function hasIndexByName(string $table, string $indexName): bool
    {
        $sql = 'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1';
        $row = $this->db->query($sql, [$table, $indexName])->getRowArray();

        return $row !== null;
    }
}