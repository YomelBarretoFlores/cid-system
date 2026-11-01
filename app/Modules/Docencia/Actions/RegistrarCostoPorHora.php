<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Models\CostoPorHora;
use App\Modules\Docencia\Models\GradoAcademico;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final readonly class RegistrarCostoPorHora
{
    public const string SUPERPOSICION = 'La vigencia se superpone con otro costo por hora de este grado académico.';

    private const string EXCLUSION_VIOLATION = '23P01';

    public function __invoke(int $gradoAcademicoId, string $costo, CarbonImmutable $desde, ?CarbonImmutable $hasta, User $usuario): CostoPorHora
    {
        $this->validar($costo, $desde, $hasta);

        if (! GradoAcademico::query()->whereKey($gradoAcademicoId)->where('activo', true)->exists()) {
            throw new ReglaDeNegocioIncumplida('El grado académico no está disponible.');
        }

        return Transaccion::ejecutar(function () use ($gradoAcademicoId, $costo, $desde, $hasta, $usuario): CostoPorHora {
            // A degree has no stable row to lock before its first cost, so its changes queue on a transaction-scoped advisory lock.
            DB::select('select pg_advisory_xact_lock(hashtext(?))', ["doc_costos_hora:grado:{$gradoAcademicoId}"]);
            $this->asegurarSinRepreciar($desde);
            $abierto = CostoPorHora::query()->where('grado_academico_id', $gradoAcademicoId)->whereNull('vigente_hasta')->first();
            $reemplazado = $abierto !== null && $desde->gt($abierto->vigente_desde) ? $abierto : null;

            if ($reemplazado instanceof CostoPorHora) {
                $this->asegurarReemplazo($desde, $hasta);
            }

            return self::traducirSuperposicion(fn (): CostoPorHora => $this->crear($gradoAcademicoId, $costo, $desde, $hasta, $usuario, $reemplazado));
        });
    }

    private function validar(string $costo, CarbonImmutable $desde, ?CarbonImmutable $hasta): void
    {
        if (preg_match('/^\d{1,8}(\.\d{1,2})?\z/', $costo) !== 1 || preg_match('/[1-9]/', $costo) !== 1) {
            throw new ReglaDeNegocioIncumplida('El costo por hora debe ser un monto mayor que cero, con hasta dos decimales.');
        }

        if ($hasta instanceof CarbonImmutable && $hasta->lt($desde)) {
            throw new ReglaDeNegocioIncumplida('La fecha de fin no puede ser anterior a la de inicio.');
        }
    }

    private function crear(int $gradoAcademicoId, string $costo, CarbonImmutable $desde, ?CarbonImmutable $hasta, User $usuario, ?CostoPorHora $reemplazado): CostoPorHora
    {
        $reemplazado?->update(['vigente_hasta' => $desde->subDay()->toDateString()]);

        return CostoPorHora::query()->create([
            'grado_academico_id' => $gradoAcademicoId,
            'costo_hora' => $costo,
            'vigente_desde' => $desde->toDateString(),
            'vigente_hasta' => $hasta?->toDateString(),
            'cerro_costo_id' => $reemplazado?->id,
            'registrado_por' => $usuario->id,
        ]);
    }

    // A degree rate wins over the condition rate on every date, so starting it in the past would reprice hours already reported.
    private function asegurarSinRepreciar(CarbonImmutable $desde): void
    {
        $hoy = CarbonImmutable::today('America/Lima')->toDateString();

        if ($desde->toDateString() > $hoy) {
            return;
        }

        $condicionCubre = CostoPorHora::query()->whereNotNull('condicion_laboral_id')
            ->where('vigente_desde', '<=', $hoy)
            ->where(fn ($vigencia) => $vigencia->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $desde->toDateString()))
            ->exists();

        if ($condicionCubre) {
            throw new ReglaDeNegocioIncumplida('Hay horas ya dictadas con el costo anterior por condición laboral; el costo por grado debe empezar después de hoy.');
        }
    }

    private function asegurarReemplazo(CarbonImmutable $desde, ?CarbonImmutable $hasta): void
    {
        // Hours already taught keep the cost that was in effect; only the future changes.
        if ($desde->toDateString() <= CarbonImmutable::today('America/Lima')->toDateString()) {
            throw new ReglaDeNegocioIncumplida('Un costo que reemplaza al vigente debe empezar después de hoy.');
        }

        if ($hasta instanceof CarbonImmutable) {
            throw new ReglaDeNegocioIncumplida('Un costo que reemplaza al vigente no lleva fecha de fin; la categoría quedaría sin costo después de esa fecha.');
        }
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
