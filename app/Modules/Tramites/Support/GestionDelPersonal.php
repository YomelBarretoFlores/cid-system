<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Staff actions check the permission, then take the person's lock and the trámite row, in the same order as Pagos events.
final class GestionDelPersonal
{
    /**
     * @param  list<EstadoTramite>  $estados
     */
    public static function bloquear(int $tramiteId, User $usuario, array $estados): Tramite
    {
        throw_unless($usuario->can(PermisoTramite::Gestionar->value), ReglaDeNegocioIncumplida::class, 'No tiene permiso para atender trámites.');

        $personaId = Tramite::query()->whereKey($tramiteId)->value('persona_id') ?? throw new ReglaDeNegocioIncumplida('El trámite no existe.');
        RegistroDeMovimiento::bloquearPersona((int) $personaId);
        $tramite = Tramite::query()->whereKey($tramiteId)->lockForUpdate()->firstOrFail();

        throw_unless(in_array($tramite->estado, $estados, true), ReglaDeNegocioIncumplida::class, "El trámite está {$tramite->estado->etiqueta()} y no admite esta acción.");

        return $tramite;
    }
}
