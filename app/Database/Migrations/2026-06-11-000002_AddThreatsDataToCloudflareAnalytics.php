<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddThreatsDataToCloudflareAnalytics extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('cloudflare_analytics', [
            'threats_data' => [
                'type' => 'JSON',
                'null' => true,
                'after' => 'threats_blocked',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('cloudflare_analytics', 'threats_data');
    }
}