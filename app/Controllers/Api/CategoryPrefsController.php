<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\UserCategoryPrefModel;

class CategoryPrefsController extends BaseApiController
{
    protected UserCategoryPrefModel $model;

    public function __construct()
    {
        $this->model = new UserCategoryPrefModel();
    }

    public function save()
    {
        $userId = $this->getAuthUserId();
        $body   = $this->request->getJSON(true) ?? [];

        $type  = strtolower(trim((string) ($body['type'] ?? '')));
        $prefs = $body['prefs'] ?? null;

        if (! in_array($type, ['live', 'vod', 'series'], true)) {
            return $this->error("'type' moet live, vod of series zijn.", 422);
        }
        if (! is_array($prefs)) {
            return $this->error("'prefs' moet een array zijn.", 422);
        }

        $this->model->replaceAllForType($userId, $type, $prefs);

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
