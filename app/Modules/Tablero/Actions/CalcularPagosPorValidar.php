<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Data\PagoPorValidar;
use App\Modules\Tablero\Data\PagosPorValidar;
use App\Modules\Tablero\Support\PeriodoConsultado;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use stdClass;

// Payments are made before the service, so "morosidad" is read as payments still waiting for validation (decision #4).
final readonly class CalcularPagosPorValidar
{
    private const int MAS_ANTIGUOS = 5;

    public function __invoke(FiltrosDelTablero $filtros): PagosPorValidar
    {
        $periodo = PeriodoConsultado::de($filtros->periodoId);
        $pendiente = EstadoPago::Pendiente->value;
        $hoy = CarbonImmutable::today('America/Lima');

        $enElPeriodo = DB::table('pag_pagos')
            ->whereBetween('fecha_pago', [$periodo->fechaInicio, $periodo->fechaFin])
            ->where('estado', '!=', EstadoPago::Anulado->value)
            ->selectRaw('count(*) as registrados, count(*) filter (where estado = ?) as pendientes', [$pendiente])
            ->first();
        $porValidar = DB::table('pag_pagos')
            ->where('estado', $pendiente)
            ->selectRaw('count(*) as total, coalesce(sum((monto * 100)::bigint), 0) as centimos, min(created_at) as primero')
            ->first();
        $primero = $porValidar->primero ?? null;

        return new PagosPorValidar(
            registrados: (int) ($enElPeriodo->registrados ?? 0),
            pendientes: (int) ($enElPeriodo->pendientes ?? 0),
            porValidar: (int) ($porValidar->total ?? 0),
            centimosPorValidar: (int) ($porValidar->centimos ?? 0),
            diasDelMasAntiguo: is_string($primero) ? $this->dias($primero, $hoy) : null,
            masAntiguos: $this->masAntiguos($hoy),
        );
    }

    /**
     * @return list<PagoPorValidar>
     */
    private function masAntiguos(CarbonImmutable $hoy): array
    {
        return array_values(DB::table('pag_pagos as p')
            ->join('pag_conceptos as c', 'c.id', '=', 'p.concepto_id')
            ->where('p.estado', EstadoPago::Pendiente->value)
            ->orderBy('p.created_at')
            ->orderBy('p.id')
            ->limit(self::MAS_ANTIGUOS)
            ->selectRaw('p.id, c.nombre as concepto, (p.monto * 100)::bigint as centimos, p.fecha_pago, p.created_at')
            ->get()
            ->map(fn (stdClass $pago): PagoPorValidar => new PagoPorValidar(
                pagoId: (int) $pago->id,
                concepto: (string) $pago->concepto,
                centimos: (int) $pago->centimos,
                fechaPago: CarbonImmutable::parse((string) $pago->fecha_pago),
                dias: $this->dias((string) $pago->created_at, $hoy),
            ))
            ->all());
    }

    private function dias(string $registradoEn, CarbonImmutable $hoy): int
    {
        return (int) CarbonImmutable::parse($registradoEn)->setTimezone('America/Lima')->startOfDay()->diffInDays($hoy);
    }
}
