<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final readonly class CerrarTarifa
{
    public const string SUPERPOSICION = 'La vigencia se superpone con otra tarifa de este concepto y vínculo.';

    private const string EXCLUSION_VIOLATION = '23P01';

    public function __invoke(int $tarifaId, CarbonImmutable $hasta): Tarifa
    {
        return DB::transaction(function () use ($tarifaId, $hasta): Tarifa {
            $tarifa = Tarifa::query()->whereKey($tarifaId)->lockForUpdate()->firstOrFail();

            if ($tarifa->vigente_hasta?->isSameDay($hasta)) {
                return $tarifa;
            }

            $this->asegurarCierrePosible($tarifa, $hasta);

            return self::traducirSuperposicion(function () use ($tarifa, $hasta): Tarifa {
                $tarifa->update(['vigente_hasta' => $hasta->toDateString()]);

                return $tarifa;
            });
        });
    }

    /**
     * @template T
     *
     * @param  callable(): T  $operacion
     * @return T
     */
    public static function traducirSuperposicion(callable $operacion): mixed
    {
        try {
            return $operacion();
        } catch (QueryException $error) {
            throw $error->getCode() === self::EXCLUSION_VIOLATION
                ? new ReglaDeNegocioIncumplida(self::SUPERPOSICION, previous: $error)
                : $error;
        }
    }

    private function asegurarCierrePosible(Tarifa $tarifa, CarbonImmutable $hasta): void
    {
        if ($hasta->lt($tarifa->vigente_desde)) {
            throw new ReglaDeNegocioIncumplida("El cierre no puede ser anterior al inicio de la vigencia ({$tarifa->vigente_desde->format('d/m/Y')}).");
        }

        $ultimoPago = Pago::query()->where('tarifa_id', $tarifa->id)->max('fecha_pago');

        if ($ultimoPago !== null && $hasta->lt(CarbonImmutable::parse($ultimoPago))) {
            $fecha = CarbonImmutable::parse($ultimoPago)->format('d/m/Y');

            throw new ReglaDeNegocioIncumplida("La tarifa se aplicó a un pago del {$fecha}; no puede cerrarse antes de esa fecha.");
        }
    }
}
