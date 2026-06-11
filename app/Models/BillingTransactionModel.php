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
     * Get paginated transactions for Tabulator server-side processing.
     *
     * @param int    $page   Current page (1-based)
     * @param int    $limit  Items per page
     * @param string $search Search term (matches user_id, product_id, plan_type, status, purchase_token, user email)
     * @param string|null $sortField Field to sort by
     * @param string $sortDir Direction ('asc' or 'desc')
     * @return array{last_page: int, data: array}
     */
    public function getPaginated(int $page = 1, int $limit = 25, string $search = '', ?string $sortField = null, string $sortDir = 'desc'): array
    {
        $builder = $this->select('billing_transactions.*, users.email as user_email')
            ->join('users', 'users.id = billing_transactions.user_id', 'left');

        // Apply search filter
        if ($search !== '') {
            $builder->groupStart()
                ->like('billing_transactions.user_id', $search)
                ->orLike('billing_transactions.product_id', $search)
                ->orLike('billing_transactions.plan_type', $search)
                ->orLike('billing_transactions.status', $search)
                ->orLike('billing_transactions.purchase_token', $search)
                ->orLike('users.email', $search)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);

        // Apply sorting
        $allowedSortFields = ['id', 'user_email', 'product_id', 'plan_type', 'amount', 'status', 'created_at'];
        if ($sortField && in_array($sortField, $allowedSortFields, true)) {
            // Prefix table name for ambiguous columns
            $prefix = in_array($sortField, ['id', 'product_id', 'plan_type', 'amount', 'status', 'created_at'], true)
                ? 'billing_transactions.' . $sortField
                : $sortField;
            $builder->orderBy($prefix, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $builder->orderBy('billing_transactions.created_at', 'DESC');
        }

        $offset = ($page - 1) * $limit;
        $data   = $builder->limit($limit, $offset)->get()->getResultArray();

        return [
            'last_page' => (int) ceil($total / $limit),
            'data'      => $data,
        ];
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