<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use App\Modules\Docencia\Policies\CostoPorHoraPolicy;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $condicion_laboral_id
 * @property ?int $grado_academico_id
 * @property ?int $cerro_costo_id
 * @property-read ?CondicionLaboral $condicionLaboral
 * @property-read ?GradoAcademico $gradoAcademico
 * @property string $costo_hora
 * @property CarbonImmutable $vigente_desde
 * @property ?CarbonImmutable $vigente_hasta
 */
#[Table('doc_costos_hora')]
#[Fillable(['condicion_laboral_id', 'grado_academico_id', 'costo_hora', 'vigente_desde', 'vigente_hasta', 'cerro_costo_id', 'registrado_por'])]
#[UsePolicy(CostoPorHoraPolicy::class)]
final class CostoPorHora extends Model
{
    /**
     * @return BelongsTo<CondicionLaboral, $this>
     */
    public function condicionLaboral(): BelongsTo
    {
        return $this->belongsTo(CondicionLaboral::class);
    }

    /**
     * @return BelongsTo<GradoAcademico, $this>
     */
    public function gradoAcademico(): BelongsTo
    {
        return $this->belongsTo(GradoAcademico::class);
    }

    // A degree rate is new; a condition rate is the history registered before rates followed the degree.
    public function categoria(): string
    {
        return $this->gradoAcademico->nombre ?? (($this->condicionLaboral->nombre ?? '—').' (tarifa anterior)');
    }

    public function claveDeBloqueo(): string
    {
        return $this->grado_academico_id !== null ? "doc_costos_hora:grado:{$this->grado_academico_id}" : "doc_costos_hora:condicion:{$this->condicion_laboral_id}";
    }

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
            'costo_hora' => 'decimal:2',
            'vigente_desde' => 'immutable_date',
            'vigente_hasta' => 'immutable_date',
        ];
    }
}
