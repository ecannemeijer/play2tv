<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAuthSecurityColumnsToUsers extends Migration
{
    public function up(): void
    {
        $fields = $this->db->getFieldData('users');
        $fieldNames = array_map(static fn(object $field): string => $field->name, $fields);

        if (! in_array('role', $fieldNames, true)) {
            $this->forge->addColumn('users', [
                'role' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'user',
                    'null'       => false,
                    'after'      => 'password',
                ],
            ]);
        }

        if (! in_array('auth_version', $fieldNames, true)) {
            $this->forge->addColumn('users', [
                'auth_version' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                    'null'       => false,
                    'after'      => 'is_active',
                ],
            ]);
        }

        if (! in_array('locked_until', $fieldNames, true)) {
            $this->forge->addColumn('users', [
                'locked_until' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'last_login_at',
                ],
            ]);
        }
    }

    public function down(): void
    {
        $fields = $this->db->getFieldData('users');
        $fieldNames = array_map(static fn(object $field): string => $field->name, $fields);

        foreach (['role', 'auth_version', 'locked_until'] as $field) {
            if (in_array($field, $fieldNames, true)) {
                $this->forge->dropColumn('users', $field);
            }
        }
    }
}