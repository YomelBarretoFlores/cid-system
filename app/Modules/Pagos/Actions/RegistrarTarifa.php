<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class RegistrarTarifa
{
    public function __construct(private CerrarTarifa $cerrarTarifa) {}

    public function __invoke(int $conceptoId, TipoVinculo $vinculo, string $monto, CarbonImmutable $desde, ?CarbonImmutable $hasta = null): Tarifa
    {
        return DB::transaction(function () use ($conceptoId, $vinculo, $monto, $desde, $hasta): Tarifa {
            // Locking the concept serializes fee changes for it.
            Concepto::query()->whereKey($conceptoId)->lockForUpdate()->firstOrFail();

            $abierta = Tarifa::query()
                ->where(['concepto_id' => $conceptoId, 'tipo_vinculo' => $vinculo])
                ->whereNull('vigente_hasta')
                ->first();

            // A fee starting before the open one fills an earlier gap; the exclusion constraint rejects any overlap.
            if ($abierta !== null && $desde->gt($abierta->vigente_desde)) {
                ($this->cerrarTarifa)($abierta->id, $desde->subDay());
            }

            return CerrarTarifa::traducirSuperposicion(fn (): Tarifa => Tarifa::query()->create([
                'concepto_id' => $conceptoId,
                'tipo_vinculo' => $vinculo,
                'monto' => $monto,
                'vigente_desde' => $desde->toDateString(),
                'vigente_hasta' => $hasta?->toDateString(),
            ]));
        });
    }
}
