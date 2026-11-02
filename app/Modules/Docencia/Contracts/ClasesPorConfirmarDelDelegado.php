<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

// How many classes a delegate still has to confirm, or null when the person is not the delegate of any group.
interface ClasesPorConfirmarDelDelegado
{
    public function __invoke(int $personaId): ?int;
}
