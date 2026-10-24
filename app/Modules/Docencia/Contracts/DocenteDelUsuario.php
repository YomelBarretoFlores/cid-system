<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

interface DocenteDelUsuario
{
    /** Returns the teacher id of the user's person, or null when the person has no teacher profile. */
    public function __invoke(int $userId): ?int;
}
