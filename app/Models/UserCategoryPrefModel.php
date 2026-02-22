<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class UserCategoryPrefModel extends Model
{
    protected $table      = 'user_category_prefs';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'type',
        'category_key',
        'display_name',
        'visible',
        'position',
        'updated_at',
    ];

    protected $useTimestamps = false;

    public function replaceAllForType(int $userId, string $type, array $prefs): void
    {
        $this->where('user_id', $userId)
             ->where('type', $type)
             ->delete();

        $rows = [];
        $now  = date('Y-m-d H:i:s');
        foreach ($prefs as $i => $pref) {
            $key = trim((string) ($pref['name'] ?? ''));
            if ($key === '') {
                continue;
            }
            $rows[] = [
                'user_id'      => $userId,
                'type'         => $type,
                'category_key' => $key,
                'display_name' => (string) ($pref['display_name'] ?? $key),
                'visible'      => !empty($pref['visible']) ? 1 : 0,
                'position'     => isset($pref['position']) ? (int) $pref['position'] : ($i * 10),
                'updated_at'   => $now,
            ];
        }

        if (! empty($rows)) {
            $this->insertBatch($rows);
        }
    }

    public function getByType(int $userId, string $type): array
    {
        return $this->where('user_id', $userId)
                    ->where('type', $type)
                    ->orderBy('position', 'ASC')
                    ->orderBy('category_key', 'ASC')
                    ->findAll();
    }
}
