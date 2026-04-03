<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class AuthRefreshTokenModel extends Model
{
    protected $table      = 'auth_refresh_tokens';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'selector',
        'family_id',
        'token_hash',
        'fingerprint_hash',
        'ip_hash',
        'user_agent_hash',
        'device_id',
        'access_jti',
        'expires_at',
        'last_used_at',
        'rotated_at',
        'revoked_at',
        'revoked_reason',
        'replaced_by_selector',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findBySelector(string $selector): ?array
    {
        return $this->where('selector', $selector)->first();
    }

    public function countActiveForUser(int $userId): int
    {
        return $this->where('user_id', $userId)
            ->where('revoked_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->countAllResults();
    }

    public function getActiveForUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->where('revoked_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('last_used_at', 'ASC')
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    public function getRecentForUser(int $userId, int $limit = 20): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('last_used_at', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->findAll(max(1, $limit));
    }

    public function revokeById(int $id, string $reason, ?string $replacedBySelector = null): void
    {
        $payload = [
            'revoked_at'     => date('Y-m-d H:i:s'),
            'revoked_reason' => substr($reason, 0, 80),
        ];

        if ($replacedBySelector !== null) {
            $payload['replaced_by_selector'] = $replacedBySelector;
            $payload['rotated_at'] = date('Y-m-d H:i:s');
        }

        $this->builder()
            ->where('id', $id)
            ->where('revoked_at', null)
            ->update($payload);
    }

    public function revokeFamily(string $familyId, string $reason): void
    {
        $this->builder()
            ->where('family_id', $familyId)
            ->where('revoked_at', null)
            ->update([
                'revoked_at'     => date('Y-m-d H:i:s'),
                'revoked_reason' => substr($reason, 0, 80),
            ]);
    }

    public function revokeUserTokens(int $userId, string $reason): void
    {
        $this->builder()
            ->where('user_id', $userId)
            ->where('revoked_at', null)
            ->update([
                'revoked_at'     => date('Y-m-d H:i:s'),
                'revoked_reason' => substr($reason, 0, 80),
            ]);
    }

    public function revokeByDeviceId(int $userId, string $deviceId, string $reason): void
    {
        $this->builder()
            ->where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->where('revoked_at', null)
            ->update([
                'revoked_at'     => date('Y-m-d H:i:s'),
                'revoked_reason' => substr($reason, 0, 80),
            ]);
    }
}