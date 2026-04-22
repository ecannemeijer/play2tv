<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class AppSettingModel extends Model
{
    protected $table = 'app_settings';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'setting_key',
        'setting_value',
        'updated_at',
    ];

    protected $useTimestamps = false;

    public function getValue(string $key): ?string
    {
        $row = $this->where('setting_key', $key)->first();

        return is_array($row) ? (($row['setting_value'] ?? null) !== '' ? (string) ($row['setting_value'] ?? '') : null) : null;
    }

    public function setValue(string $key, ?string $value): void
    {
        $existing = $this->where('setting_key', $key)->first();
        $data = [
            'setting_key' => $key,
            'setting_value' => $value,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (is_array($existing)) {
            $this->update((int) $existing['id'], $data);
            return;
        }

        $this->insert($data);
    }
}