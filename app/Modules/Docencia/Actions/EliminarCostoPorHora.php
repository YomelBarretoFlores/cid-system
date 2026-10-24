<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Models\CostoPorHora;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class EliminarCostoPorHora
{
    public function __invoke(int $costoId): void
    {
        Transaccion::ejecutar(function () use ($costoId): void {
            // Same advisory lock as registering, taken first, so both queue per category in one order.
            $categoria = CostoPorHora::query()->whereKey($costoId)->firstOrFail()->condicion_laboral;
            DB::select('select pg_advisory_xact_lock(hashtext(?))', ["doc_costos_hora:{$categoria->value}"]);
            $costo = CostoPorHora::query()->whereKey($costoId)->lockForUpdate()->firstOrFail();

            // A cost already in effect fed reports that may have been delivered; only a future one can go.
            if (! self::aunNoVigente($costo)) {
                throw new ReglaDeNegocioIncumplida('Solo se puede eliminar un costo que aún no entra en vigencia; para cambiarlo, registra uno nuevo.');
            }

            if (CostoPorHora::query()->where('cerro_costo_id', $costo->id)->exists()) {
                throw new ReglaDeNegocioIncumplida('Otro costo más reciente ya reemplazó a este; elimina primero el más reciente.');
            }

            $cerrado = $costo->cerro_costo_id === null ? null : CostoPorHora::query()->whereKey($costo->cerro_costo_id)->lockForUpdate()->first();

            $costo->delete();
            $cerrado?->update(['vigente_hasta' => null]);
        });
    }

    public static function aunNoVigente(CostoPorHora $costo): bool
    {
        return $costo->vigente_desde->toDateString() > CarbonImmutable::today('America/Lima')->toDateString();
    }
}
