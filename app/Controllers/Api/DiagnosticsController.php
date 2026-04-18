<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\SecurityEventService;
use CodeIgniter\HTTP\Files\UploadedFile;
use DateTimeImmutable;
use DateTimeZone;

class DiagnosticsController extends BaseApiController
{
    private SecurityEventService $events;

    public function __construct()
    {
        $this->events = new SecurityEventService();
    }

    public function upload()
    {
        try {
            if (($response = $this->guardUploadApiKey()) !== null) {
                return $response;
            }

            $deviceId = $this->sanitizeDeviceId((string) $this->request->getPost('device_id'));
            if ($deviceId === '') {
                return $this->error('Device ID is verplicht.', 422, ['device_id' => 'Device ID ontbreekt.']);
            }

            $file = $this->request->getFile('log_bundle');
            if (! $file instanceof UploadedFile) {
                return $this->error('Logbestand ontbreekt.', 422, ['log_bundle' => 'Geen bestand ontvangen.']);
            }

            if (! $file->isValid()) {
                return $this->error('Logbestand upload mislukt.', 422, ['log_bundle' => $file->getErrorString()]);
            }

            $fileSize = $file->getSize();
            $mimeType = (string) $file->getMimeType();
            $maxUploadBytes = max(1024, (int) env('diagnostics.maxUploadBytes', 1048576));
            if ($fileSize > $maxUploadBytes) {
                return $this->error('Logbestand is te groot.', 422, [
                    'log_bundle' => sprintf('Maximaal %d bytes toegestaan.', $maxUploadBytes),
                ]);
            }

            if (! $this->isAllowedUploadMimeType($mimeType)) {
                return $this->error('Ongeldig logbestand.', 422, ['log_bundle' => 'Alleen tekstbestanden zijn toegestaan.']);
            }

            $uploadDir = WRITEPATH . 'uploads/logs/';
            if (! is_dir($uploadDir) && ! @mkdir($uploadDir, 0775, true) && ! is_dir($uploadDir)) {
                return $this->error('Uploadmap kon niet worden aangemaakt.', 500);
            }

            $storedName = $this->buildStoredFileName($deviceId, $uploadDir);
            $file->move($uploadDir, $storedName);

            $uploadedAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM);
            $this->events->log('diagnostics_upload_success', 'info', $this->request, null, [
                'route' => 'api/diagnostics/upload',
                'device_id' => $deviceId,
                'stored_name' => $storedName,
                'size' => $fileSize,
                'mime_type' => $mimeType,
            ]);

            return $this->created([
                'device_id' => $deviceId,
                'file_name' => $storedName,
                'size' => $fileSize,
                'uploaded_at' => $uploadedAt,
            ], 'Support log uploaded.');
        } catch (\Throwable $exception) {
            log_message('error', 'Diagnostics upload failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return $this->error('Logbestand kon niet worden opgeslagen.', 500);
        }
    }

    private function guardUploadApiKey(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        $expectedApiKey = trim((string) env('diagnostics.uploadApiKey', ''));
        $providedApiKey = trim($this->request->getHeaderLine('X-Velixa-API-Key'));

        if ($expectedApiKey === '') {
            log_message('warning', 'Diagnostics upload rejected because diagnostics.uploadApiKey is not configured.');
            return $this->error('Diagnostics upload is not configured.', 503);
        }

        if (! hash_equals($expectedApiKey, $providedApiKey)) {
            $this->events->log('diagnostics_upload_api_key_invalid', 'warning', $this->request, null, [
                'route' => 'api/diagnostics/upload',
                'device_id' => $this->sanitizeDeviceId((string) $this->request->getPost('device_id')),
            ]);

            return $this->error('Ongeldige API-sleutel.', 401);
        }

        return null;
    }

    private function sanitizeDeviceId(string $value): string
    {
        $clean = strtolower($this->sanitizeText($value, 120));
        $clean = preg_replace('/[^a-z0-9._-]+/', '-', $clean) ?? '';
        $clean = trim($clean, '.-_');

        return $clean;
    }

    private function isAllowedUploadMimeType(string $mimeType): bool
    {
        if ($mimeType === '') {
            return true;
        }

        return in_array($mimeType, [
            'text/plain',
            'text/x-log',
            'application/octet-stream',
        ], true);
    }

    private function buildStoredFileName(string $deviceId, string $uploadDir): string
    {
        $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Ymd_His_v');
        $baseName = $deviceId . '_' . $timestamp;
        $storedName = $baseName . '.log';
        $suffix = 1;

        while (is_file($uploadDir . $storedName)) {
            $storedName = $baseName . '_' . $suffix . '.log';
            $suffix++;
        }

        return $storedName;
    }
}