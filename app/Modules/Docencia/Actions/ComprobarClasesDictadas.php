<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Contracts\ClasesDictadasDelGrupo;
use App\Modules\Docencia\Models\ClaseDictada;

final readonly class ComprobarClasesDictadas implements ClasesDictadasDelGrupo
{
    public function __invoke(int $grupoId): bool
    {
        return ClaseDictada::query()->where('grupo_id', $grupoId)->exists();
    }
}
