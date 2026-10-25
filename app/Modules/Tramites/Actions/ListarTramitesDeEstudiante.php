<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Models\Tramite;
use Illuminate\Database\Eloquent\Collection;

// A student's own trámites, newest first, for the portal page and the API.
final readonly class ListarTramitesDeEstudiante
{
    /**
     * @return Collection<int, Tramite>
     */
    public function __invoke(int $personaId): Collection
    {
        return Tramite::query()->where('persona_id', $personaId)->latest()->latest('id')->get();
    }
}
