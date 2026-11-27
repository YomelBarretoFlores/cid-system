<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Actions\DesactivarCuentaDelPortal;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class DarDeBajaCuentaDelPortal
{
    public function __construct(private DesactivarCuentaDelPortal $desactivar) {}

    public function __invoke(int $personaId, string $motivo, User $actor): void
    {
        throw_unless($actor->can(PermisoPersona::Gestionar->value), AuthorizationException::class);
        $usuarioId = Persona::query()->findOrFail($personaId)->user_id ?? throw new ReglaDeNegocioIncumplida('Esta persona no tiene cuenta del portal.');

        ($this->desactivar)($usuarioId, $motivo, $actor);
    }
}
