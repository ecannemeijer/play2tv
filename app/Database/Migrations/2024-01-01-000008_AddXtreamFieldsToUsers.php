<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Add Xtream Codes parameters to users table
 *
 * Allows admin to assign per-user Xtream Codes credentials.
 * The Android app receives these on login and can auto-add the playlist.
 *
 * Fields added to users:
 *   xtream_server    — e.g. http://stream.example.com:8080
 *   xtream_username  — Xtream account username
 *   xtream_password  — Xtream account password
 */
class AddXtreamFieldsToUsers extends Migration
{
    public function up(): void
    {
        $fields = [
            'xtream_server' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
                'after'      => 'is_active',
            ],
            'xtream_username' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'xtream_server',
            ],
            'xtream_password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'xtream_username',
            ],
        ];

        $this->forge->addColumn('users', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', ['xtream_server', 'xtream_username', 'xtream_password']);
    }
}
