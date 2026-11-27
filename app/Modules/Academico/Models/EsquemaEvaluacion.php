<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\EsquemaEvaluacionFactory;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Enums\RegimenDeEsquema;
use App\Modules\Academico\Policies\OfertaAcademicaPolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nombre
 * @property ?int $idioma_id
 * @property ?NivelCurricular $nivel
 * @property ?int $programa_id
 * @property ?RegimenDeEsquema $regimen
 * @property string $nota_maxima
 * @property string $nota_minima_aprobatoria
 * @property CarbonInterface $vigente_desde
 * @property ?CarbonInterface $vigente_hasta
 */
#[Table('aca_esquemas_evaluacion')]
#[Fillable([
    'nombre', 'idioma_id', 'nivel', 'programa_id', 'regimen', 'nota_maxima', 'nota_minima_aprobatoria',
    'vigente_desde', 'vigente_hasta',
])]
#[UseFactory(EsquemaEvaluacionFactory::class)]
#[UsePolicy(OfertaAcademicaPolicy::class)]
final class EsquemaEvaluacion extends Model
{
    /** @use HasFactory<EsquemaEvaluacionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Idioma, $this>
     */
    public function idioma(): BelongsTo
    {
        return $this->belongsTo(Idioma::class);
    }

    /**
     * @return BelongsTo<Programa, $this>
     */
    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class);
    }

    /**
     * @return HasMany<ComponenteEvaluacion, $this>
     */
    public function componentes(): HasMany
    {
        return $this->hasMany(ComponenteEvaluacion::class, 'esquema_id')->orderBy('orden');
    }

    /**
     * Schemes in force on a day for a course, the most specific first: regime weighs most (only an accelerated scheme can grade two
     * cycles), then program, language and level; a scheme without program or regime applies to all of them.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function aplicables(Builder $query, string $dia, ?int $idiomaId, NivelCurricular $nivel, ?int $programaId = null, ?RegimenDeEsquema $regimen = null): void
    {
        $query->where('vigente_desde', '<=', $dia)
            ->where(fn (Builder $vigencia) => $vigencia->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $dia))
            ->where(fn (Builder $alcance) => $alcance->whereNull('idioma_id')->orWhere('idioma_id', $idiomaId))
            ->where(fn (Builder $alcance) => $alcance->whereNull('nivel')->orWhere('nivel', $nivel))
            ->where(fn (Builder $alcance) => $alcance->whereNull('programa_id')->when($programaId !== null, fn (Builder $mismo) => $mismo->orWhere('programa_id', $programaId)))
            ->where(fn (Builder $alcance) => $alcance->whereNull('regimen')->when($regimen instanceof RegimenDeEsquema, fn (Builder $mismo) => $mismo->orWhere('regimen', $regimen)))
            ->orderByRaw('regimen IS NULL, programa_id IS NULL, idioma_id IS NULL, nivel IS NULL')
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id');
    }

    protected function casts(): array
    {
        return [
            'nivel' => NivelCurricular::class,
            'regimen' => RegimenDeEsquema::class,
            'nota_maxima' => 'decimal:2',
            'nota_minima_aprobatoria' => 'decimal:2',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }
}
