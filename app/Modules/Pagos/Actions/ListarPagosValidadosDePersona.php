<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Contracts\PagosValidadosDePersona;
use App\Modules\Pagos\Data\PagoValidado;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Pago;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final readonly class ListarPagosValidadosDePersona implements PagosValidadosDePersona
{
    public function __invoke(int $personaId, TipoConcepto $servicio): array
    {
        $pagos = Pago::query()
            ->with('concepto')
            ->where(['persona_id' => $personaId, 'estado' => EstadoPago::Validado])
            ->whereHas('concepto', fn (Builder $concepto) => $concepto->where('tipo', $servicio))
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id')
            ->get();

        return array_values($pagos->map(fn (Pago $pago): PagoValidado => new PagoValidado(
            id: $pago->id,
            concepto: $pago->concepto->nombre,
            numeroOperacion: $pago->numero_operacion,
            monto: (string) $pago->monto,
            fechaPago: CarbonImmutable::parse($pago->fecha_pago),
        ))->all());
    }
}
