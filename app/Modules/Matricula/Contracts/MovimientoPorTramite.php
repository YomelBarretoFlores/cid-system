<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Contracts;

use App\Shared\Auth\Models\User;

interface MovimientoPorTramite
{
    /** Transfers to the destination group, or withdraws when it is null, with the same rules and permission as the panel. */
    public function __invoke(int $matriculaId, ?int $grupoDestinoId, string $motivo, User $autorizadoPor): void;
}
