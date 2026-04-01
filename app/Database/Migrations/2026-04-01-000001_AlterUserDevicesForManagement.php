<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterUserDevicesForManagement extends Migration
{
    public function up(): void
    {
        $fields = $this->db->getFieldData('user_devices');
        $fieldNames = array_map(static fn ($field) => strtolower($field->name), $fields);

        if (! in_array('device_name', $fieldNames, true)) {
            $this->forge->addColumn('user_devices', [
                'device_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'after' => 'device_id',
                ],
            ]);
        }

        if (! in_array('last_used', $fieldNames, true)) {
            $this->forge->addColumn('user_devices', [
                'last_used' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'device_name',
                ],
            ]);
        }

        if (in_array('last_seen', $fieldNames, true)) {
            $this->db->query('UPDATE user_devices SET last_used = COALESCE(last_used, last_seen)');
            $this->forge->dropColumn('user_devices', 'last_seen');
        }

        $this->db->query("UPDATE user_devices SET device_name = COALESCE(NULLIF(device_name, ''), CONCAT('Device ', id)) WHERE device_name IS NULL OR device_name = ''");
    }

    public function down(): void
    {
        $fields = $this->db->getFieldData('user_devices');
        $fieldNames = array_map(static fn ($field) => strtolower($field->name), $fields);

        if (! in_array('last_seen', $fieldNames, true)) {
            $this->forge->addColumn('user_devices', [
                'last_seen' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'device_id',
                ],
            ]);
            if (in_array('last_used', $fieldNames, true)) {
                $this->db->query('UPDATE user_devices SET last_seen = COALESCE(last_seen, last_used)');
            }
        }

        if (in_array('last_used', $fieldNames, true)) {
            $this->forge->dropColumn('user_devices', 'last_used');
        }

        if (in_array('device_name', $fieldNames, true)) {
            $this->forge->dropColumn('user_devices', 'device_name');
        }
    }
}