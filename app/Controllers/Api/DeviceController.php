<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\UserDeviceModel;
use App\Models\UserModel;

class DeviceController extends BaseApiController
{
    private UserDeviceModel $deviceModel;
    private UserModel $userModel;

    public function __construct()
    {
        $this->deviceModel = new UserDeviceModel();
        $this->userModel = new UserModel();
    }

    public function show($userId = null)
    {
        $userId = (int) $userId;
        $authUserId = $this->getAuthUserId();

        if ($userId !== $authUserId) {
            return $this->error('Geen toegang tot deze apparaten.', 403);
        }

        $currentDeviceId = trim((string) ($this->request->getGet('current_device_id') ?? ''));
        if ($currentDeviceId !== '') {
            $this->deviceModel->touchDevice($authUserId, $currentDeviceId, null, $this->request->getIPAddress());
        }

        return $this->ok($this->buildDevicePayload($authUserId));
    }

    public function register()
    {
        $body = $this->getJsonBody(['user_id', 'device_id', 'device_name']);
        if ($body === false) {
            return $this->error('Ongeldige apparaataanvraag.', 422, $this->getValidationErrors());
        }

        if (! $this->validatePayload($body, [
            'user_id'     => 'required|integer|greater_than[0]',
            'device_id'   => 'required|max_length[255]',
            'device_name' => 'required|max_length[120]',
        ])) {
            return $this->error('Apparaatvalidatie mislukt.', 422, $this->getValidationErrors());
        }

        $userId = (int) ($body['user_id'] ?? 0);
        $deviceId = $this->sanitizeText((string) ($body['device_id'] ?? ''), 255);
        $deviceName = $this->sanitizeText((string) ($body['device_name'] ?? ''), 120);

        $guard = $this->guardDeviceAction($userId, $deviceId, $deviceName);
        if ($guard !== null) {
            return $guard;
        }

        $existing = $this->deviceModel->findByUserAndDevice($userId, $deviceId);
        if (! $existing && $this->deviceModel->countDistinctDevicesForUser($userId) >= UserDeviceModel::MAX_DEVICES) {
            return $this->error('Apparaatlimiet bereikt. Vervang eerst een bestaand apparaat.', 409);
        }

        $this->deviceModel->registerDevice($userId, $deviceId, $deviceName, $this->request->getIPAddress());

        return $this->ok($this->buildDevicePayload($userId), 'Apparaat geregistreerd.');
    }

    public function replace()
    {
        $body = $this->getJsonBody(['user_id', 'old_device_id', 'new_device_id', 'device_name']);
        if ($body === false) {
            return $this->error('Ongeldige vervangingsaanvraag.', 422, $this->getValidationErrors());
        }

        if (! $this->validatePayload($body, [
            'user_id'       => 'required|integer|greater_than[0]',
            'old_device_id' => 'required|max_length[255]',
            'new_device_id' => 'required|max_length[255]',
            'device_name'   => 'required|max_length[120]',
        ])) {
            return $this->error('Vervangingsvalidatie mislukt.', 422, $this->getValidationErrors());
        }

        $userId = (int) ($body['user_id'] ?? 0);
        $oldDeviceId = $this->sanitizeText((string) ($body['old_device_id'] ?? ''), 255);
        $newDeviceId = $this->sanitizeText((string) ($body['new_device_id'] ?? ''), 255);
        $deviceName = $this->sanitizeText((string) ($body['device_name'] ?? ''), 120);

        $guard = $this->guardDeviceAction($userId, $newDeviceId, $deviceName);
        if ($guard !== null) {
            return $guard;
        }

        if ($oldDeviceId === '') {
            return $this->error('Kies een bestaand apparaat om te vervangen.', 422);
        }

        $existingOld = $this->deviceModel->findByUserAndDevice($userId, $oldDeviceId);
        if (! $existingOld) {
            return $this->error('Het geselecteerde apparaat bestaat niet meer.', 404);
        }

        $this->deviceModel->replaceDevice($userId, $oldDeviceId, $newDeviceId, $deviceName, $this->request->getIPAddress());

        return $this->ok($this->buildDevicePayload($userId), 'Apparaat vervangen.');
    }

    private function guardDeviceAction(int $userId, string $deviceId, string $deviceName): ?\CodeIgniter\HTTP\ResponseInterface
    {
        if ($userId !== $this->getAuthUserId()) {
            return $this->error('Geen toegang tot dit account.', 403);
        }

        $user = $this->userModel->find($userId);
        if (! $user) {
            return $this->error('Gebruiker niet gevonden.', 404);
        }

        if (! $this->userModel->isPremium($user)) {
            return $this->error('Alleen premium accounts kunnen apparaten beheren.', 403);
        }

        if ($deviceId === '') {
            return $this->error('Apparaat-ID is verplicht.', 422);
        }

        if ($deviceName === '') {
            return $this->error('Apparaatnaam is verplicht.', 422);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDevicePayload(int $userId): array
    {
        return [
            'user_id' => $userId,
            'max_devices' => UserDeviceModel::MAX_DEVICES,
            'devices' => $this->deviceModel->getDevicesForUser($userId),
        ];
    }
}