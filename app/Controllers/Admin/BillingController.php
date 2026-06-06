<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BillingTransactionModel;

class BillingController extends BaseController
{
    private BillingTransactionModel $billingModel;

    public function __construct()
    {
        $this->billingModel = new BillingTransactionModel();
    }

    /**
     * GET /admin/billing
     * Overview of all billing transactions.
     */
    public function index(): string
    {
        $transactions = $this->billingModel->getAllWithUser();
        $stats        = $this->billingModel->getStats();

        return view('admin/billing/index', [
            'title'        => 'Billing transacties',
            'transactions' => $transactions,
            'stats'        => $stats,
        ]);
    }

    /**
     * GET /admin/billing/(:num)
     * Detail view of a single transaction.
     */
    public function view(int $id): string
    {
        $transaction = $this->billingModel->getWithUser($id);

        if (! $transaction) {
            return view('admin/errors/404', ['title' => 'Niet gevonden']);
        }

        // Decode raw_response JSON for display
        if (! empty($transaction['raw_response']) && is_string($transaction['raw_response'])) {
            $transaction['raw_response'] = json_decode($transaction['raw_response'], true);
        }

        return view('admin/billing/view', [
            'title'       => 'Transactie #' . $id,
            'transaction' => $transaction,
        ]);
    }
}