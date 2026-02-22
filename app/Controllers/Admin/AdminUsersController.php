<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminModel;

/**
 * AdminUsersController
 *
 * CRUD for admin accounts in the admin panel.
 * Password change is separate (own account or any admin).
 */
class AdminUsersController extends BaseController
{
    protected AdminModel $adminModel;

    public function __construct()
    {
        $this->adminModel = new AdminModel();
    }

    // ----------------------------------------------------------------
    // index — list all admins
    // ----------------------------------------------------------------
    public function index(): string
    {
        $admins = $this->adminModel->orderBy('created_at', 'DESC')->findAll();

        return view('admin/admins/index', [
            'admins'       => $admins,
            'currentAdmin' => session()->get('admin_id'),
        ]);
    }

    // ----------------------------------------------------------------
    // create — GET: show form / POST: store
    // ----------------------------------------------------------------
    public function create(): mixed
    {
        if ($this->request->getMethod() === 'POST') {
            $rules = [
                'username' => 'required|min_length[3]|max_length[50]|is_unique[admins.username]',
                'password' => 'required|min_length[8]',
                'password_confirm' => 'required|matches[password]',
            ];

            if (! $this->validate($rules)) {
                return view('admin/admins/create', [
                    'errors' => $this->validator->getErrors(),
                    'old'    => $this->request->getPost(),
                ]);
            }

            $this->adminModel->insert([
                'username' => $this->request->getPost('username'),
                'password' => $this->request->getPost('password'),
            ]);

            return redirect()->to(base_url('admin/admins'))
                             ->with('success', 'Admin aangemaakt.');
        }

        return view('admin/admins/create', ['errors' => [], 'old' => []]);
    }

    // ----------------------------------------------------------------
    // edit — GET: show form / POST: update username (not password)
    // ----------------------------------------------------------------
    public function edit(int $id): mixed
    {
        $admin = $this->adminModel->find($id);
        if (! $admin) {
            return redirect()->to(base_url('admin/admins'))
                             ->with('error', 'Admin niet gevonden.');
        }

        if ($this->request->getMethod() === 'POST') {
            $rules = [
                'username' => "required|min_length[3]|max_length[50]|is_unique[admins.username,id,{$id}]",
            ];

            if (! $this->validate($rules)) {
                return view('admin/admins/edit', [
                    'admin'  => $admin,
                    'errors' => $this->validator->getErrors(),
                ]);
            }

            $this->adminModel->update($id, [
                'username' => $this->request->getPost('username'),
            ]);

            return redirect()->to(base_url('admin/admins'))
                             ->with('success', 'Admin bijgewerkt.');
        }

        return view('admin/admins/edit', ['admin' => $admin, 'errors' => []]);
    }

    // ----------------------------------------------------------------
    // delete — prevent self-delete, then remove
    // ----------------------------------------------------------------
    public function delete(int $id): mixed
    {
        if ((int) session()->get('admin_id') === $id) {
            return redirect()->to(base_url('admin/admins'))
                             ->with('error', 'Je kunt je eigen account niet verwijderen.');
        }

        $admin = $this->adminModel->find($id);
        if (! $admin) {
            return redirect()->to(base_url('admin/admins'))
                             ->with('error', 'Admin niet gevonden.');
        }

        $this->adminModel->delete($id);

        return redirect()->to(base_url('admin/admins'))
                         ->with('success', 'Admin verwijderd.');
    }

    // ----------------------------------------------------------------
    // changePassword — change own password (or any admin if super)
    // ----------------------------------------------------------------
    public function changePassword(): mixed
    {
        $currentId = (int) session()->get('admin_id');

        if ($this->request->getMethod() === 'POST') {
            $rules = [
                'current_password'  => 'required',
                'new_password'      => 'required|min_length[8]',
                'password_confirm'  => 'required|matches[new_password]',
            ];

            if (! $this->validate($rules)) {
                return view('admin/admins/change_password', [
                    'errors' => $this->validator->getErrors(),
                ]);
            }

            $admin = $this->adminModel->find($currentId);
            if (! $this->adminModel->verifyPassword(
                $this->request->getPost('current_password'),
                $admin['password']
            )) {
                return view('admin/admins/change_password', [
                    'errors' => ['current_password' => 'Huidig wachtwoord is onjuist.'],
                ]);
            }

            $this->adminModel->update($currentId, [
                'password' => $this->request->getPost('new_password'),
            ]);

            return redirect()->to(base_url('admin/admins'))
                             ->with('success', 'Wachtwoord gewijzigd.');
        }

        return view('admin/admins/change_password', ['errors' => []]);
    }
}
