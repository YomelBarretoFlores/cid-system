<?php

declare(strict_types=1);

namespace App\Shared\Support\Database;

use Illuminate\Database\UniqueConstraintViolationException;

final class RestriccionUnica
{
    // PostgreSQL quotes the violated index in its message; the project runs only on PostgreSQL (ADR 0002).
    public static function es(UniqueConstraintViolationException $error, string $indice): bool
    {
        return str_contains($error->getMessage(), "\"{$indice}\"");
    }
}
