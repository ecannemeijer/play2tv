<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create billing_transactions table
 *
 * Stores every Google Play purchase processed via the API
 * so admins can review all payments in the admin panel.
 */
class CreateBillingTransactionsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'product_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'purchase_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 1024,
            ],
            'plan_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'yearly / lifetime / monthly',
            ],
            'amount' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'comment'    => 'Formatted price from Google (e.g. €49,99)',
            ],
            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'completed',
                'comment'    => 'completed / refunded / cancelled',
            ],
            'google_order_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'premium_duration' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'comment'    => 'e.g. +1 year, +10 years',
            ],
            'raw_response' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Full Google Play purchase response for debugging',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('product_id');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');

        $this->forge->createTable('billing_transactions');
    }

    public function down(): void
    {
        $this->forge->dropTable('billing_transactions');
    }
}