<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\PeriodoFactory;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Policies\OfertaAcademicaPolicy;
use App\Shared\Support\Eloquent\RenuevaContenidoPublico;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property EstadoPeriodo $estado
 * @property CarbonImmutable $fecha_inicio
 * @property CarbonImmutable $fecha_fin
 * @property CarbonImmutable $matricula_inicio
 * @property CarbonImmutable $matricula_fin
 * @property CarbonImmutable|null $matricula_extemporanea_fin
 * @property CarbonImmutable|null $actas_inicio
 * @property CarbonImmutable|null $actas_fin
 */
#[Table('aca_periodos')]
#[Fillable(['anio', 'numero', 'nombre', 'fecha_inicio', 'fecha_fin', 'matricula_inicio', 'matricula_fin', 'matricula_extemporanea_fin', 'actas_inicio', 'actas_fin', 'estado'])]
#[UseFactory(PeriodoFactory::class)]
#[UsePolicy(OfertaAcademicaPolicy::class)]
final class Periodo extends Model
{
    // Matches the aca_periodos_numero_check constraint: at most twelve periods in a year.
    public const int NUMERO_MAXIMO = 12;

    /** @use HasFactory<PeriodoFactory> */
    use HasFactory, RenuevaContenidoPublico;

    #[\Override]
    protected $attributes = [
        'estado' => 'planificado',
    ];

    /**
     * Periods whose enrollment, regular or late, is running on that date.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function conMatriculaEn(Builder $query, string $fecha): void
    {
        $query->where('matricula_inicio', '<=', $fecha)->whereRaw('coalesce(matricula_extemporanea_fin, matricula_fin) >= ?', [$fecha]);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function conMatriculaPendienteDesde(Builder $query, string $fecha): void
    {
        $query->whereRaw('coalesce(matricula_extemporanea_fin, matricula_fin) >= ?', [$fecha]);
    }

    /**
     * @return HasMany<Grupo, $this>
     */
    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'numero' => 'integer',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'matricula_inicio' => 'date',
            'matricula_fin' => 'date',
            'matricula_extemporanea_fin' => 'date',
            'actas_inicio' => 'date',
            'actas_fin' => 'date',
            'estado' => EstadoPeriodo::class,
        ];
    }
}
