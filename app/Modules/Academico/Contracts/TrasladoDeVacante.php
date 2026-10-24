<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

interface TrasladoDeVacante
{
    /** Locks both groups until the caller's transaction ends. */
    public function __invoke(int $grupoOrigenId, int $grupoDestinoId): void;
}
