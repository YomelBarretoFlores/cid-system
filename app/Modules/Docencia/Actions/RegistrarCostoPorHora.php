<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;

final readonly class RegistrarCostoPorHora
{
    public const string SUPERPOSICION = 'La vigencia se superpone con otro costo por hora de esta categoría.';

    private const string EXCLUSION_VIOLATION = '23P01';

    public function __invoke(CondicionLaboral $categoria, string $costo, CarbonImmutable $desde, ?CarbonImmutable $hasta, User $usuario): CostoPorHora
    {
        if (preg_match('/^\d{1,8}(\.\d{1,2})?\z/', $costo) !== 1 || preg_match('/[1-9]/', $costo) !== 1) {
            throw new ReglaDeNegocioIncumplida('El costo por hora debe ser un monto mayor que cero, con hasta dos decimales.');
        }

        if ($hasta instanceof CarbonImmutable && $hasta->lt($desde)) {
            throw new ReglaDeNegocioIncumplida('La fecha de fin no puede ser anterior a la de inicio.');
        }

        return Transaccion::ejecutar(function () use ($categoria, $costo, $desde, $hasta, $usuario): CostoPorHora {
            // Locking the open cost serializes changes of the category; the exclusion constraint rejects any overlap.
            $abierto = CostoPorHora::query()->where('condicion_laboral', $categoria)->whereNull('vigente_hasta')->lockForUpdate()->first();

            return $this->traducirSuperposicion(function () use ($abierto, $categoria, $costo, $desde, $hasta, $usuario): CostoPorHora {
                if ($abierto !== null && $desde->gt($abierto->vigente_desde)) {
                    $abierto->update(['vigente_hasta' => $desde->subDay()->toDateString()]);
                }

                return CostoPorHora::query()->create([
                    'condicion_laboral' => $categoria,
                    'costo_hora' => $costo,
                    'vigente_desde' => $desde->toDateString(),
                    'vigente_hasta' => $hasta?->toDateString(),
                    'registrado_por' => $usuario->id,
                ]);
            });
        });
    }

    /**
     * @template T
     *
     * @param  callable(): T  $operacion
     * @return T
     */
    private function traducirSuperposicion(callable $operacion): mixed
    {
        try {
            return $operacion();
        } catch (QueryException $error) {
            throw $error->getCode() === self::EXCLUSION_VIOLATION ? new ReglaDeNegocioIncumplida(self::SUPERPOSICION, previous: $error) : $error;
        }
    }
}
