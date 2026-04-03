<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\AuthContext;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $requiredRole = strtolower((string) ($arguments[0] ?? 'user'));
        $actualRole   = strtolower((string) (AuthContext::get()?->role ?? 'user'));

        if ($requiredRole !== $actualRole) {
            return response()
                ->setStatusCode(403)
                ->setContentType('application/json')
                ->setJSON([
                    'success' => false,
                    'message' => 'Onvoldoende rechten voor deze actie.',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}