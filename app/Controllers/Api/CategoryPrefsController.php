<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\UserCategoryPrefModel;

class CategoryPrefsController extends BaseApiController
{
    public function __construct()
    {
        $this->model = new UserCategoryPrefModel();
    }

    public function save()
    {
        $userId = $this->getAuthUserId();
        $body   = $this->getJsonBody(['type', 'prefs']);

        if ($body === false) {
            return $this->error('Ongeldige categoriepayload.', 422, $this->getValidationErrors());
        }

        if (! $this->validatePayload($body, [
            'type' => 'required|in_list[live,vod,series]',
        ])) {
            return $this->error('Categorievalidatie mislukt.', 422, $this->getValidationErrors());
        }

        $type  = strtolower(trim((string) ($body['type'] ?? '')));
        $prefs = $body['prefs'] ?? null;

        if (! is_array($prefs)) {
            return $this->error("'prefs' moet een array zijn.", 422);
        }

        $sanitizedPrefs = array_map(function (mixed $pref): array {
            $row = is_array($pref) ? $pref : [];

            return [
                'name'         => $this->sanitizeText((string) ($row['name'] ?? ''), 120),
                'display_name' => $this->sanitizeText((string) ($row['display_name'] ?? ''), 120),
                'visible'      => (bool) ($row['visible'] ?? true),
                'position'     => max(0, (int) ($row['position'] ?? 0)),
            ];
        }, $prefs);

        $this->model->replaceAllForType($userId, $type, $sanitizedPrefs);

        return $this->ok([], 'Categorievoorkeuren opgeslagen.');
    }

    public function index()
    {
        $userId = $this->getAuthUserId();
        $type   = strtolower(trim((string) ($this->request->getGet('type') ?? '')));

        if (! in_array($type, ['live', 'vod', 'series'], true)) {
            return $this->error("Query 'type' moet live, vod of series zijn.", 422);
        }

        $rows = $this->model->getByType($userId, $type);

        return $this->ok([
            'type'  => $type,
            'prefs' => array_map(static function (array $row): array {
                return [
                    'name'         => $row['category_key'],
                    'display_name' => $row['display_name'],
                    'visible'      => (bool) $row['visible'],
                    'position'     => (int) $row['position'],
                ];
            }, $rows),
        ]);
    }
}
