<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Pagos\Contracts\ConsultaDePago;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\AplicacionDePago;
use App\Modules\Tramites\Support\RegistroDeMovimiento;
use App\Shared\Support\Database\Transaccion;
use Illuminate\Database\Eloquent\Builder;

// A newly validated fee goes to the person's oldest trámite waiting for that concept type (or observed only for a rejected receipt); running it twice changes nothing.
final readonly class AplicarPagoValidado
{
    public function __construct(
        private ConsultaDePago $consultaDePago,
        private AplicacionDePago $aplicacionDePago,
    ) {}

    public function __invoke(int $pagoId): ?Tramite
    {
        $pago = ($this->consultaDePago)($pagoId);

        if ($pago->estado !== EstadoPago::Validado || ! in_array($pago->tipo, GuardarTipoDeTramite::CONCEPTOS, true)) {
            return null;
        }

        return Transaccion::ejecutar(function () use ($pago, $pagoId): ?Tramite {
            RegistroDeMovimiento::bloquearPersona($pago->personaId);

            $tramite = Tramite::query()
                ->where(['persona_id' => $pago->personaId, 'tipo_concepto' => $pago->tipo->value])
                ->whereNull('pago_id')
                ->where(fn (Builder $esperando) => $esperando
                    ->where('estado', EstadoTramite::PendientePago->value)
                    ->orWhere(fn (Builder $rechazado) => $rechazado->where('estado', EstadoTramite::Observado->value)->where('observacion', ObservarPorPagoRechazado::OBSERVACION)))
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($tramite === null || Tramite::query()->where('pago_id', $pagoId)->exists()) {
                return null;
            }

            $this->aplicacionDePago->aplicar($tramite, $pagoId);

            return $tramite;
        });
    }
}
