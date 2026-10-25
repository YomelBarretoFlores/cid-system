<?php

declare(strict_types=1);

namespace App\Shared\Support\Cache;

use RuntimeException;
use Throwable;

// Carries an error of the page builder past the cache fallback, so only cache failures are absorbed.
final class FalloAlConstruirContenido extends RuntimeException
{
    public function __construct(Throwable $error)
    {
        parent::__construct($error->getMessage(), (int) $error->getCode(), $error);
    }
}
