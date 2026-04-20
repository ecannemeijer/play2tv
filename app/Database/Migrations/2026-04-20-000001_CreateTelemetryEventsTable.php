<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTelemetryEventsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'severity' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'info',
            ],
            'app_version' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'app_code' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'device_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'android_version' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'channel_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'last_action' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'stream_type' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'client_timestamp' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ip_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'fingerprint_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'data_json' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['created_at']);
        $this->forge->addKey(['event_type', 'created_at']);
        $this->forge->addKey(['severity', 'created_at']);
        $this->forge->addKey(['channel_name', 'created_at']);
        $this->forge->addKey(['app_version', 'created_at']);
        $this->forge->createTable('telemetry_events', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('telemetry_events', true);
    }
}