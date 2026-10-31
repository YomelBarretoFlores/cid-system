<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Contracts\ResumenDePagosDePersona;
use App\Modules\Pagos\Data\PagoRechazado;
use App\Modules\Pagos\Data\ResumenDePagos;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Contracts\ConsultaDeVinculo;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final readonly class ResumirPagosDePersona implements ResumenDePagosDePersona
{
    public function __construct(private ConsultaDeVinculo $vinculo) {}

    public function __invoke(int $personaId): ResumenDePagos
    {
        $pagos = Pago::query()->with('concepto:id,nombre,tipo')->where('persona_id', $personaId)
            ->orderByDesc('fecha_pago')->orderByDesc('id')->get();
        $ultimo = $pagos->first();

        return new ResumenDePagos(
            porValidar: $pagos->where('estado', EstadoPago::Pendiente)->count(),
            validados: $pagos->where('estado', EstadoPago::Validado)->count(),
            matriculaPorValidar: $pagos->contains(fn (Pago $pago): bool => $pago->estado === EstadoPago::Pendiente && $pago->concepto->tipo === TipoConcepto::Matricula),
            ultimoRechazo: $ultimo?->estado === EstadoPago::Rechazado
                ? new PagoRechazado($ultimo->concepto->nombre, $ultimo->monto, $ultimo->fecha_pago, (string) $ultimo->motivo_rechazo)
                : null,
            montoDeMatricula: $this->montoDeMatricula($personaId),
        );
    }

    private function montoDeMatricula(int $personaId): ?string
    {
        $monto = Tarifa::query()
            ->whereHas('concepto', fn (Builder $concepto) => $concepto->where(['tipo' => TipoConcepto::Matricula, 'activo' => true]))
            ->where('tipo_vinculo', ($this->vinculo)($personaId))
            ->vigenteEn(CarbonImmutable::today('America/Lima'))
            ->orderBy('monto')
            ->value('monto');

        return is_string($monto) ? $monto : null;
    }
}
