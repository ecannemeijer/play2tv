<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * AdminModel
 *
 * Handles admin panel authentication.
 * Admin accounts are stored in the 'admins' table.
 * Session-based authentication (not JWT) for the admin panel.
 */
class AdminModel extends Model
{
    protected $table      = 'admins';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'username',
        'password',
    ];

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPasswordOnUpdate'];

    /**
     * Hash password before insert
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
            unset($data['data']['password']);
        }
        return $data;
    }

    /**
     * Find admin by username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first();
    }

    /**
     * Verify plain password against stored bcrypt hash
     */
    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
