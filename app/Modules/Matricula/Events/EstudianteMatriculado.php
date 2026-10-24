<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class EstudianteMatriculado implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $matriculaId,
        public int $personaId,
        public int $grupoId,
    ) {}
}
