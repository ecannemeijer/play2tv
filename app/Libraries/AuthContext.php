<?php

declare(strict_types=1);

namespace App\Libraries;

class AuthContext
{
    private static ?object $payload = null;

    public static function set(object $payload): void
    {
        self::$payload = $payload;
    }

    public static function get(): ?object
    {
        return self::$payload;
    }

    public static function clear(): void
    {
        self::$payload = null;
    }
}