<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Pagos\Contracts\ConsultaDePago;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\AplicacionDePago;
use App\Modules\Tramites\Support\RegistroDeMovimiento;
use App\Shared\Support\Database\Transaccion;

// A rejected receipt tells the student why the trámite that waits for it is stuck; the next validated payment clears it.
final readonly class ObservarPorPagoRechazado
{
    public const string OBSERVACION = 'El CILN rechazó el pago de la tasa que registraste. Registra de nuevo el recibo correcto en Registro de pagos para continuar.';

    public function __construct(private ConsultaDePago $consultaDePago) {}

    public function __invoke(int $pagoId): ?Tramite
    {
        $pago = ($this->consultaDePago)($pagoId);

        if (! in_array($pago->tipo, GuardarTipoDeTramite::CONCEPTOS, true)) {
            return null;
        }

        return Transaccion::ejecutar(function () use ($pago): ?Tramite {
            RegistroDeMovimiento::bloquearPersona($pago->personaId);

            $tramite = Tramite::query()
                ->where(['persona_id' => $pago->personaId, 'tipo_concepto' => $pago->tipo->value, 'estado' => EstadoTramite::PendientePago->value])
                ->whereNull('pago_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->first(fn (Tramite $candidato): bool => AplicacionDePago::admite($candidato, $pago->conceptoId, $pago->nivel));

            if ($tramite === null) {
                return null;
            }

            $tramite->update(['estado' => EstadoTramite::Observado, 'observacion' => self::OBSERVACION]);
            RegistroDeMovimiento::registrar($tramite, comentario: self::OBSERVACION);

            return $tramite;
        });
    }
}
