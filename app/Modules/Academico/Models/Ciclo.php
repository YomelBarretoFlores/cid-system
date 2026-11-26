<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\CicloFactory;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Policies\OfertaAcademicaPolicy;
use App\Shared\Support\Eloquent\RenuevaContenidoPublico;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Idioma $idioma
 * @property NivelCurricular $nivel
 */
#[Table('aca_ciclos')]
#[Fillable(['idioma_id', 'numero', 'nivel', 'nombre', 'horas_lectivas', 'activo'])]
#[UseFactory(CicloFactory::class)]
#[UsePolicy(OfertaAcademicaPolicy::class)]
final class Ciclo extends Model
{
    /** @use HasFactory<CicloFactory> */
    use HasFactory, RenuevaContenidoPublico;

    /**
     * @return BelongsTo<Idioma, $this>
     */
    public function idioma(): BelongsTo
    {
        return $this->belongsTo(Idioma::class);
    }

    /**
     * The active cycle right after the given one in the same language and level: the second cycle of an accelerated group.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function siguienteDelNivel(Builder $query, self $ciclo): void
    {
        $query->where(['idioma_id' => $ciclo->idioma_id, 'nivel' => $ciclo->nivel, 'numero' => $ciclo->numero + 1, 'activo' => true]);
    }

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'nivel' => NivelCurricular::class,
            'horas_lectivas' => 'integer',
            'activo' => 'boolean',
        ];
    }
}
