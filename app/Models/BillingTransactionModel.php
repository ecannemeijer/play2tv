<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class BillingTransactionModel extends Model
{
    protected $table      = 'billing_transactions';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'product_id',
        'purchase_token',
        'plan_type',
        'amount',
        'currency',
        'status',
        'google_order_id',
        'premium_duration',
        'raw_response',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Map a product_id to a human-readable plan type.
     */
    public function planTypeFromProductId(string $productId): string
    {
        return match ($productId) {
            'velixa_premium_yearly'   => 'yearly',
            'velixa_premium_lifetime' => 'lifetime',
            'velixa_premium_monthly'  => 'monthly',
            default                   => 'unknown',
        };
    }

    /**
     * Log a purchase transaction to the database.
     */
    public function logTransaction(array $data): int|false
    {
        return $this->insert([
            'user_id'          => $data['user_id'],
            'product_id'       => $data['product_id'],
            'purchase_token'   => $data['purchase_token'] ?? '',
            'plan_type'        => $data['plan_type'] ?? $this->planTypeFromProductId($data['product_id']),
            'amount'           => $data['amount'] ?? null,
            'currency'         => $data['currency'] ?? null,
            'status'           => $data['status'] ?? 'completed',
            'google_order_id'  => $data['google_order_id'] ?? null,
            'premium_duration' => $data['premium_duration'] ?? null,
            'raw_response'     => isset($data['raw_response']) ? json_encode($data['raw_response']) : null,
        ], true);
    }

    /**
     * Get all transactions with user email, ordered by newest first.
     */
    public function getAllWithUser(): array
    {
        return $this->select('billing_transactions.*, users.email as user_email')
            ->join('users', 'users.id = billing_transactions.user_id', 'left')
            ->orderBy('billing_transactions.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get transactions for a specific user.
     */
    public function getByUserId(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get a single transaction with user info.
     */
    public function getWithUser(int $id): ?array
    {
        return $this->select('billing_transactions.*, users.email as user_email')
            ->join('users', 'users.id = billing_transactions.user_id', 'left')
            ->where('billing_transactions.id', $id)
            ->first();
    }

    /**
     * Get summary statistics.
     */
    public function getStats(): array
    {
        $total = $this->countAllResults(false);
        $yearly = $this->where('plan_type', 'yearly')->countAllResults(false);
        $lifetime = $this->where('plan_type', 'lifetime')->countAllResults(false);
        $completed = $this->where('status', 'completed')->countAllResults(false);

        return [
            'total'     => $total,
            'yearly'    => $yearly,
            'lifetime'  => $lifetime,
            'completed' => $completed,
        ];
    }
}