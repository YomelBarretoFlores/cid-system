<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\AplicacionDePago;
use App\Modules\Tramites\Support\GestionDelPersonal;
use App\Modules\Tramites\Support\RegistroDeMovimiento;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;

// Resuming goes back to waiting for the fee when it is still unpaid, and takes a free payment if the student already has one.
final readonly class ReanudarTramite
{
    public function __construct(private AplicacionDePago $aplicacionDePago) {}

    public function __invoke(int $tramiteId, User $usuario): Tramite
    {
        return Transaccion::ejecutar(function () use ($tramiteId, $usuario): Tramite {
            $tramite = GestionDelPersonal::bloquear($tramiteId, $usuario, [EstadoTramite::Observado]);
            $tasaPendiente = $tramite->pago_id === null ? $tramite->tipo_concepto : null;

            $tramite->update(['estado' => $tasaPendiente === null ? EstadoTramite::EnProceso : EstadoTramite::PendientePago, 'observacion' => null]);
            $pagoLibre = $tasaPendiente === null ? null : $this->aplicacionDePago->pagoLibre($tramite->persona_id, $tasaPendiente);
            RegistroDeMovimiento::registrar($tramite, $usuario->id, notificar: $pagoLibre === null);

            if ($pagoLibre !== null) {
                $this->aplicacionDePago->aplicar($tramite, $pagoLibre);
            }

            return $tramite;
        });
    }
}
