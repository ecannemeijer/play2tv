<?php

declare(strict_types=1);

namespace App\Filters;

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

        if (! $session->get('admin_id')) {
            return redirect()->to(base_url('admin/login'))
                             ->with('error', 'U moet ingelogd zijn om dit te bekijken.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Not used
    }
}
