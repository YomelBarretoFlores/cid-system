<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Contracts\ClasesPorConfirmarDelDelegado;
use App\Modules\Docencia\Support\BandejaDelDelegado;

final readonly class ContarClasesPorConfirmarDelDelegado implements ClasesPorConfirmarDelDelegado
{
    public function __construct(private BandejaDelDelegado $bandeja) {}

    public function __invoke(int $personaId): ?int
    {
        $clases = ($this->bandeja)($personaId);

        return $clases->esDelegado ? $clases->pendientesDelDelegado() : null;
    }
}
