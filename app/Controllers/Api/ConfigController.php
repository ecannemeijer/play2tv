<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\TelemetryConfigProvider;

class ConfigController extends BaseApiController
{
    private TelemetryConfigProvider $telemetryConfig;

    public function __construct()
    {
        $this->telemetryConfig = new TelemetryConfigProvider();
    }

    public function show()
    {
        return $this->withCorsHeaders($this->respond($this->telemetryConfig->getConfig(), 200));
    }
}