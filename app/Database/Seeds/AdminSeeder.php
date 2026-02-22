<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * AdminSeeder
 *
 * Seeds the initial admin account and optional demo data.
 *
 * Run with:
 *   php spark db:seed AdminSeeder
 *
 * WARNING: Change the password immediately after first login!
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $db = \Config\Database::connect();

        // ── Create default admin account ──────────────────────────────────────
        $username = 'admin';
        $password = 'Admin@Play2TV!'; // CHANGE THIS IMMEDIATELY

        // Check if admin already exists
        $existing = $db->table('admins')->where('username', $username)->get()->getRowArray();

        if (! $existing) {
            $db->table('admins')->insert([
                'username'   => $username,
                'password'   => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            echo "\n✅ Admin aangemaakt:\n";
            echo "   Gebruikersnaam: {$username}\n";
            echo "   Wachtwoord:     {$password}\n";
            echo "   ⚠️  Wijzig het wachtwoord meteen na eerste login!\n\n";
        } else {
            echo "\n⚠️  Admin bestaat al, overgeslagen.\n\n";
        }

        echo "🚀 Seeding voltooid.\n";
    }
}
