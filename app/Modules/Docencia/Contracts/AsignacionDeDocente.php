<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

use App\Shared\Auth\Models\User;

// Puts a teacher in charge of a group from another module's screen, with the same rules as the teacher record.
interface AsignacionDeDocente
{
    public function __invoke(int $grupoId, int $docenteId, User $actor): void;
}
