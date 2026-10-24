<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Policies\CostoPorHoraPolicy;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CondicionLaboral $condicion_laboral
 * @property string $costo_hora
 * @property CarbonImmutable $vigente_desde
 * @property ?CarbonImmutable $vigente_hasta
 */
#[Table('doc_costos_hora')]
#[Fillable(['condicion_laboral', 'costo_hora', 'vigente_desde', 'vigente_hasta', 'cerro_costo_id', 'registrado_por'])]
#[UsePolicy(CostoPorHoraPolicy::class)]
final class CostoPorHora extends Model
{
    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function vigenteEn(Builder $query, CarbonInterface $fecha): void
    {
        $query->where('vigente_desde', '<=', $fecha->toDateString())
            ->where(fn (Builder $vigencia) => $vigencia->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fecha->toDateString()));
    }

    protected function casts(): array
    {
        return [
            'condicion_laboral' => CondicionLaboral::class,
            'costo_hora' => 'decimal:2',
            'vigente_desde' => 'immutable_date',
            'vigente_hasta' => 'immutable_date',
        ];
    }
}
