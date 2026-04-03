<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\AdminModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AdminAuthFilter
 *
 * Protects all /admin/* routes (except /admin/login).
 * Uses session-based authentication for the admin panel.
 *
 * If session does not contain a valid admin_id, redirect to login.
 */
class AdminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $adminId = (int) $session->get('admin_id');

        if ($adminId <= 0) {
            return redirect()->to(base_url('admin/login'))
                             ->with('error', 'U moet ingelogd zijn om dit te bekijken.');
        }

        if ((new AdminModel())->find($adminId) === null) {
            $session->remove(['admin_id', 'admin_username']);

            return redirect()->to(base_url('admin/login'))
                             ->with('error', 'Administratorsessie is niet meer geldig.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Not used
    }
}
