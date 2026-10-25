<?php

declare(strict_types=1);

namespace App\Shared\Auth\Data;

use Carbon\CarbonImmutable;

final readonly class TokenDeApi
{
    /**
     * @param  array{estudiante: bool, docente: bool}  $perfiles
     */
    public function __construct(
        public string $token,
        public CarbonImmutable $expiraEn,
        public array $perfiles,
    ) {}
}
