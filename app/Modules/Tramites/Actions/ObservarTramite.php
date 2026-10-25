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

final readonly class ObservarTramite
{
    public function __invoke(int $tramiteId, User $usuario, string $motivo): Tramite
    {
        $motivo = trim($motivo);
        throw_unless(mb_strlen($motivo) >= 5 && mb_strlen($motivo) <= 500, ReglaDeNegocioIncumplida::class, 'Indique la observación, entre 5 y 500 caracteres.');

        return Transaccion::ejecutar(function () use ($tramiteId, $usuario, $motivo): Tramite {
            $tramite = GestionDelPersonal::bloquear($tramiteId, $usuario, [EstadoTramite::EnProceso, EstadoTramite::PendientePago]);

            $tramite->update(['estado' => EstadoTramite::Observado, 'observacion' => $motivo]);
            RegistroDeMovimiento::registrar($tramite, $usuario->id, $motivo);

            return $tramite;
        });
    }
}
