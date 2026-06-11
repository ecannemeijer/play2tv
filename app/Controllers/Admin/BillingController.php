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
     * Overview page with stats (Tabulator loads data via AJAX).
     */
    public function index(): string
    {
        $stats = $this->billingModel->getStats();

        return view('admin/billing/index', [
            'title' => 'Billing transacties',
            'stats' => $stats,
        ]);
    }

    /**
     * GET /admin/billing/data (AJAX)
     * Returns JSON for Tabulator server-side pagination/search/sort.
     */
    public function getData(): \CodeIgniter\HTTP\ResponseInterface
    {
        $page   = (int) ($this->request->getGet('page') ?? 1);
        $size   = (int) ($this->request->getGet('size') ?? 25);
        $search = '';

        // Tabulator sends filters as filters[0][value], filters[0][field], etc.
        $filters = $this->request->getGet('filters') ?? [];
        if (! empty($filters) && isset($filters[0]['value'])) {
            $search = trim((string) $filters[0]['value']);
        }

        // Sorting: Tabulator sends sorters[0][field] and sorters[0][dir]
        $sorters   = $this->request->getGet('sorters') ?? [];
        $sortField = $sorters[0]['field'] ?? null;
        $sortDir   = $sorters[0]['dir'] ?? 'desc';

        $result = $this->billingModel->getPaginated($page, $size, $search, $sortField, $sortDir);

        // Format data for Tabulator display — ONLY include fields used by defined columns
        $data = array_map(function ($tx) {
            return [
                'id'              => (int) $tx['id'],
                'user_id'         => (int) $tx['user_id'],
                'user_email'      => $tx['user_email'] ?? 'Onbekend',
                'product_id'      => $tx['product_id'],
                'plan_type'       => $tx['plan_type'] ?? 'unknown',
                'amount'          => $tx['amount'] ?? '—',
                'currency_display' => $tx['currency'] ?? '',
                'status'          => $tx['status'] ?? 'unknown',
                'created_at'      => date('d-m-Y H:i', strtotime($tx['created_at'] ?? 'now')),
                'created_at_raw'  => $tx['created_at'] ?? null,
            ];
        }, $result['data']);

        return $this->response->setJSON([
            'last_page'  => $result['last_page'],
            'data'       => $data,
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