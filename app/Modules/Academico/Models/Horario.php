<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\HorarioFactory;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Policies\HorarioPolicy;
use App\Shared\Support\Eloquent\RenuevaContenidoPublico;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Grupo $grupo
 */
#[Table('aca_horarios')]
#[Fillable(['grupo_id', 'dia_semana', 'hora_inicio', 'hora_fin'])]
#[UseFactory(HorarioFactory::class)]
#[UsePolicy(HorarioPolicy::class)]
final class Horario extends Model
{
    /** @use HasFactory<HorarioFactory> */
    use HasFactory, RenuevaContenidoPublico;

    public const array DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    /**
     * @return BelongsTo<Grupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function seCruzaCon(Builder $query, int $diaSemana, string $inicio, string $fin): void
    {
        $query->where('dia_semana', $diaSemana)
            ->where('hora_inicio', '<', $fin)
            ->where('hora_fin', '>', $inicio);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ocupanAula(Builder $query, int $aulaId, int $periodoId, int $exceptoGrupoId): void
    {
        // A cancelled group no longer holds its classroom.
        $query->where('grupo_id', '!=', $exceptoGrupoId)
            ->whereHas('grupo', fn (Builder $grupo) => $grupo
                ->where('aula_id', $aulaId)
                ->where('periodo_id', $periodoId)
                ->where('estado', '!=', EstadoGrupo::Cancelado->value));
    }

    /**
     * @return Attribute<non-falsy-string, never>
     */
    protected function descripcion(): Attribute
    {
        return Attribute::get(fn (): string => sprintf(
            '%s de %s a %s',
            self::DIAS[$this->dia_semana] ?? (string) $this->dia_semana,
            substr((string) $this->hora_inicio, 0, 5),
            substr((string) $this->hora_fin, 0, 5),
        ));
    }

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
        ];
    }
}
