<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create watch_history table
 *
 * Tracks what movies/episodes users have watched and their progress.
 * Android app sends: content_type, content_id, season, episode, progress_seconds
 * Upsert logic: update if record exists, insert if not.
 */
class CreateWatchHistoryTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'content_type' => [
                // 'movie' or 'series'
                'type'       => 'ENUM',
                'constraint' => ['movie', 'series'],
                'null'       => false,
            ],
            'content_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'season' => [
                'type'       => 'INT',
                'constraint' => 5,
                'null'       => true,
                'default'    => null,
            ],
            'episode' => [
                'type'       => 'INT',
                'constraint' => 5,
                'null'       => true,
                'default'    => null,
            ],
            'progress_seconds' => [
                'type'    => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'watched_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'content_type', 'content_id', 'season', 'episode'], false, true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('watch_history', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('watch_history', true);
    }
}
