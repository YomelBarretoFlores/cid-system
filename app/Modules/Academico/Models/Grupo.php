<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\GrupoFactory;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\Modalidad;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('aca_grupos')]
#[RouteKey('ulid')]
#[Fillable([
    'periodo_id', 'ciclo_id', 'aula_id', 'codigo', 'modalidad', 'es_acelerado',
    'enlace_virtual', 'cupo_maximo', 'cupo_minimo', 'estado',
])]
#[UseFactory(GrupoFactory::class)]
final class Grupo extends Model
{
    /** @use HasFactory<GrupoFactory> */
    use HasFactory, HasUlids;

    protected $attributes = [
        'matriculados' => 0,
        'estado' => 'programado',
        'es_acelerado' => false,
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * @return BelongsTo<Periodo, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    /**
     * @return BelongsTo<Ciclo, $this>
     */
    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class);
    }

    /**
     * @return BelongsTo<Aula, $this>
     */
    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class);
    }

    /**
     * @return HasMany<Horario, $this>
     */
    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class)->orderBy('dia_semana')->orderBy('hora_inicio');
    }

    /**
     * @return Attribute<int, never>
     */
    protected function vacantesDisponibles(): Attribute
    {
        return Attribute::get(fn (): int => $this->cupo_maximo - $this->matriculados);
    }

    protected function casts(): array
    {
        return [
            'modalidad' => Modalidad::class,
            'estado' => EstadoGrupo::class,
            'es_acelerado' => 'boolean',
            'cupo_maximo' => 'integer',
            'cupo_minimo' => 'integer',
            'matriculados' => 'integer',
        ];
    }
}
