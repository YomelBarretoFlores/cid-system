<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Enums\TemaDeInterfaz;
use App\Shared\Auth\Models\User;

final readonly class GuardarTemaDeInterfaz
{
    public function __invoke(int $usuarioId, TemaDeInterfaz $tema): void
    {
        User::query()->whereKey($usuarioId)->update(['tema' => $tema->value]);
    }
}
