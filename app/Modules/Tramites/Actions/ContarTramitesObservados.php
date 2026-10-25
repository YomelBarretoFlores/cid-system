<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Contracts\TramitesObservados;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;

final readonly class ContarTramitesObservados implements TramitesObservados
{
    public function __invoke(int $personaId): int
    {
        return Tramite::query()->where(['persona_id' => $personaId, 'estado' => EstadoTramite::Observado])->count();
    }
}
