<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserModel
 *
 * Handles all user authentication and profile operations.
 *
 * Android API usage example:
 *   POST /api/login  → Body: {"email":"user@example.com","password":"secret","device_id":"abc123"}
 *   Response: {"token":"eyJ...", "premium": true, "premium_until": "2025-12-31"}
 */
class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'email',
        'password',
        'premium',
        'premium_until',
        'is_active',
        'last_login_ip',
        'last_login_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'email'    => 'required|valid_email|max_length[255]',
        'password' => 'required|min_length[8]|max_length[255]',
    ];

    protected $validationMessages = [
        'email' => [
            'required'    => 'E-mailadres is verplicht.',
            'valid_email' => 'Ongeldig e-mailadres.',
        ],
        'password' => [
            'required'    => 'Wachtwoord is verplicht.',
            'min_length'  => 'Wachtwoord moet minimaal 8 tekens bevatten.',
        ],
    ];

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPasswordOnUpdate'];

    /**
     * Hash password before insert using bcrypt
     */
    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash(
                $data['data']['password'],
                PASSWORD_BCRYPT,
                ['cost' => 12]
            );
        }
        return $data;
    }

    /**
     * Hash password on update only when it's being changed
     */
    protected function hashPasswordOnUpdate(array $data): array
    {
        if (isset($data['data']['password']) && $data['data']['password'] !== '') {
            $data['data']['password'] = password_hash(
                $data['data']['password'],
                PASSWORD_BCRYPT,
                ['cost' => 12]
            );
        } elseif (isset($data['data']['password'])) {
            // Remove empty password from update set
            unset($data['data']['password']);
        }
        return $data;
    }

    /**
     * Find user by email (for login)
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Verify plain password against stored hash
     */
    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    /**
     * Check if user has active premium subscription
     */
    public function isPremium(array $user): bool
    {
        if (! $user['premium']) {
            return false;
        }

        if ($user['premium_until'] && strtotime($user['premium_until']) < time()) {
            // Premium expired — update flag
            $this->update($user['id'], ['premium' => 0, 'premium_until' => null]);
            return false;
        }

        return true;
    }

    /**
     * Update last login metadata
     */
    public function recordLogin(int $userId, string $ip): void
    {
        $this->update($userId, [
            'last_login_ip' => $ip,
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get all users with total points (for admin panel)
     */
    public function getAllWithPoints(): array
    {
        return $this->db->table('users u')
            ->select('u.*, COALESCE(SUM(sp.points), 0) AS total_points')
            ->join('store_points sp', 'sp.user_id = u.id', 'left')
            ->groupBy('u.id')
            ->orderBy('u.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }
}
