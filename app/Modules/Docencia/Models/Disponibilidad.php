<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use App\Modules\Docencia\Policies\DisponibilidadPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $dia_semana
 * @property string $hora_inicio
 * @property string $hora_fin
 */
#[Table('doc_disponibilidades')]
#[Fillable(['docente_id', 'dia_semana', 'hora_inicio', 'hora_fin'])]
#[UsePolicy(DisponibilidadPolicy::class)]
final class Disponibilidad extends Model
{
    public const array DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    /**
     * @return BelongsTo<Docente, $this>
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
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
     * @return Attribute<non-falsy-string, never>
     */
    protected function descripcion(): Attribute
    {
        return Attribute::get(fn (): string => sprintf(
            '%s de %s a %s',
            self::DIAS[$this->dia_semana] ?? (string) $this->dia_semana,
            substr($this->hora_inicio, 0, 5),
            substr($this->hora_fin, 0, 5),
        ));
    }
}
