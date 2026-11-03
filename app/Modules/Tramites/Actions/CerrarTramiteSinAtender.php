<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\GestionDelPersonal;
use App\Modules\Tramites\Support\RegistroDeMovimiento;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

// Ends a trámite requested by mistake or abandoned without running its final action; an applied fee stays applied.
final readonly class CerrarTramiteSinAtender
{
    public function __invoke(int $tramiteId, User $usuario, string $motivo): Tramite
    {
        $motivo = trim($motivo);
        throw_unless(mb_strlen($motivo) >= 5 && mb_strlen($motivo) <= 500, ReglaDeNegocioIncumplida::class, 'Indique el motivo del cierre, entre 5 y 500 caracteres.');

        return Transaccion::ejecutar(function () use ($tramiteId, $usuario, $motivo): Tramite {
            $tramite = GestionDelPersonal::bloquear($tramiteId, $usuario, [EstadoTramite::PendientePago, EstadoTramite::EnProceso, EstadoTramite::Observado]);

            $tramite->update([
                'estado' => EstadoTramite::Cerrado,
                'observacion' => null,
                'motivo_cierre' => $motivo,
                'cerrado_en' => CarbonImmutable::now(),
            ]);
            RegistroDeMovimiento::registrar($tramite, $usuario->id, $motivo);

            return $tramite;
        });
    }
}
