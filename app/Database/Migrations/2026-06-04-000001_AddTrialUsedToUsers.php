<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTrialUsedToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'trial_used' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                'after'      => 'premium_until',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'trial_used');
    }
}