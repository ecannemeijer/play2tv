<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class SecurityEventModel extends Model
{
    protected $table      = 'security_events';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'event_type',
        'severity',
        'ip_hash',
        'fingerprint_hash',
        'route',
        'details',
        'created_at',
    ];

    protected $useTimestamps = false;
}