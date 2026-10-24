<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Models\CostoPorHora;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

final readonly class EliminarCostoPorHora
{
    public function __invoke(int $costoId): void
    {
        Transaccion::ejecutar(function () use ($costoId): void {
            $costo = CostoPorHora::query()->whereKey($costoId)->lockForUpdate()->firstOrFail();

            // A cost already in effect fed reports that may have been delivered; only a future one can go.
            if (! self::aunNoVigente($costo)) {
                throw new ReglaDeNegocioIncumplida('Solo se puede eliminar un costo que aún no entra en vigencia; para cambiarlo, registra uno nuevo.');
            }

            $anterior = $costo->vigente_hasta === null
                ? CostoPorHora::query()->where('condicion_laboral', $costo->condicion_laboral)->whereDate('vigente_hasta', $costo->vigente_desde->subDay())->lockForUpdate()->first()
                : null;

            $costo->delete();
            $anterior?->update(['vigente_hasta' => null]);
        });
    }

    public static function aunNoVigente(CostoPorHora $costo): bool
    {
        return $costo->vigente_desde->toDateString() > CarbonImmutable::today('America/Lima')->toDateString();
    }
}
