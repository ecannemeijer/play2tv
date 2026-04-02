<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\UserBootstrapBuilder;
use App\Models\UserModel;

class BootstrapController extends BaseApiController
{
    private UserModel $userModel;
    private UserBootstrapBuilder $bootstrapBuilder;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->bootstrapBuilder = new UserBootstrapBuilder();
    }

    public function index()
    {
        $userId = $this->getAuthUserId();
        $user = $this->userModel->find($userId);

        if (! $user) {
            return $this->error('Gebruiker niet gevonden.', 404);
        }

        $currentDeviceId = trim((string) ($this->request->getGet('current_device_id') ?? ''));

        return $this->ok(
            $this->bootstrapBuilder->buildForUser($user, $currentDeviceId, $this->request->getIPAddress()),
            'Bootstrap geladen.'
        );
    }
}