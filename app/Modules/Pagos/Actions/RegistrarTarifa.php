<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Contracts\CatalogoDeVinculos;
use App\Modules\Personas\Data\TipoDeVinculo;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

final readonly class RegistrarTarifa
{
    public const string VINCULO_NO_DISPONIBLE = 'El tipo de vínculo no existe o no está disponible para tarifas nuevas.';

    public function __construct(private CerrarTarifa $cerrarTarifa, private CatalogoDeVinculos $vinculos) {}

    public function __invoke(int $conceptoId, string $vinculo, string $monto, CarbonImmutable $desde, ?CarbonImmutable $hasta = null, ?NivelCurricular $nivel = null, ?int $programaId = null): Tarifa
    {
        $disponible = array_any(($this->vinculos)(), fn (TipoDeVinculo $tipo): bool => $tipo->codigo === $vinculo && $tipo->activo);

        if (! $disponible) {
            throw new ReglaDeNegocioIncumplida(self::VINCULO_NO_DISPONIBLE);
        }

        return Transaccion::ejecutar(function () use ($conceptoId, $vinculo, $monto, $desde, $hasta, $nivel, $programaId): Tarifa {
            // Locking the concept serializes fee changes for it.
            Concepto::query()->whereKey($conceptoId)->lockForUpdate()->firstOrFail();

            $abierta = Tarifa::query()
                ->where(['concepto_id' => $conceptoId, 'tipo_vinculo' => $vinculo, 'nivel' => $nivel?->value, 'programa_id' => $programaId])
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
                'nivel' => $nivel,
                'programa_id' => $programaId,
            ]));
        });
    }
}
