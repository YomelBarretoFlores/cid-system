<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

interface LiberacionDeVacante
{
    /** Locks the group row until the caller's transaction ends. */
    public function __invoke(int $grupoId): void;
}
