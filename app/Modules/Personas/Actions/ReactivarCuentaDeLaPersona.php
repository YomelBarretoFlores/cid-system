<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Actions\ReactivarCuentaDelPortal;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class ReactivarCuentaDeLaPersona
{
    public function __construct(private ReactivarCuentaDelPortal $reactivar) {}

    public function __invoke(int $personaId, User $actor): void
    {
        throw_unless($actor->can(PermisoPersona::Gestionar->value), AuthorizationException::class);
        $usuarioId = Persona::query()->findOrFail($personaId)->user_id ?? throw new ReglaDeNegocioIncumplida('Esta persona no tiene cuenta del portal.');

        ($this->reactivar)($usuarioId, $actor);
    }
}
