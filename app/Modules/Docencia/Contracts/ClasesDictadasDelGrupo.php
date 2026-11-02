<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

interface ClasesDictadasDelGrupo
{
    /** Whether the group already has a recorded class; call it with the group row locked, the lock class recording takes. */
    public function __invoke(int $grupoId): bool;
}
