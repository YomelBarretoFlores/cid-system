<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Support;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

// The single fee lookup: payment registration, the portal and the cashier all pick the fee here.
final readonly class TarifaAplicable
{
    /** The most specific fee in force: program and level, then level, then program, then the general fee. */
    public static function buscar(int $conceptoId, TipoVinculo $vinculo, CarbonInterface $fecha, ?NivelCurricular $nivel = null, ?int $programaId = null, bool $bloquear = false): ?Tarifa
    {
        return self::vigentes($conceptoId, $vinculo, $fecha)
            ->where(fn (Builder $query): Builder => $query->whereNull('nivel')->when($nivel, fn (Builder $q, NivelCurricular $n): Builder => $q->orWhere('nivel', $n->value)))
            ->where(fn (Builder $query): Builder => $query->whereNull('programa_id')->when($programaId, fn (Builder $q, int $id): Builder => $q->orWhere('programa_id', $id)))
            ->orderByRaw('(nivel IS NOT NULL)::int * 2 + (programa_id IS NOT NULL)::int DESC')
            ->when($bloquear, fn (Builder $query): Builder => $query->sharedLock())
            ->first();
    }

    /**
     * Whether the fees in force of the concept vary by level and by program, so the payer must say which.
     *
     * @return array{nivel: bool, programa: bool}
     */
    public static function criterios(int $conceptoId, TipoVinculo $vinculo, CarbonInterface $fecha): array
    {
        $fila = self::vigentes($conceptoId, $vinculo, $fecha)
            ->toBase()
            ->selectRaw('coalesce(bool_or(nivel IS NOT NULL), false) AS nivel, coalesce(bool_or(programa_id IS NOT NULL), false) AS programa')
            ->first();

        return ['nivel' => (bool) ($fila->nivel ?? false), 'programa' => (bool) ($fila->programa ?? false)];
    }

    /**
     * @return Builder<Tarifa>
     */
    private static function vigentes(int $conceptoId, TipoVinculo $vinculo, CarbonInterface $fecha): Builder
    {
        return Tarifa::query()
            ->where(['concepto_id' => $conceptoId, 'tipo_vinculo' => $vinculo])
            ->vigenteEn($fecha);
    }
}
