<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\GestionDelPersonal;
use App\Modules\Tramites\Support\RegistroDeMovimiento;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Moves to the next stage entered by staff; the last stage is reached only through FinalizarTramite.
final readonly class AvanzarTramite
{
    public function __invoke(int $tramiteId, User $usuario): Tramite
    {
        return Transaccion::ejecutar(function () use ($tramiteId, $usuario): Tramite {
            $tramite = GestionDelPersonal::bloquear($tramiteId, $usuario, [EstadoTramite::EnProceso]);

            throw_unless($tramite->condicionDeLaSiguiente() === CondicionDeEtapa::AccionDelPersonal, ReglaDeNegocioIncumplida::class, 'La siguiente etapa no la avanza el personal.');
            throw_if($tramite->etapa_actual + 1 === count($tramite->etapas) - 1, ReglaDeNegocioIncumplida::class, 'La siguiente es la última etapa: finalice el trámite.');

            $tramite->update(['etapa_actual' => $tramite->etapa_actual + 1]);
            RegistroDeMovimiento::registrar($tramite, $usuario->id);

            return $tramite;
        });
    }
}
