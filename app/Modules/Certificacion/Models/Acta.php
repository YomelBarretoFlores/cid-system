<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Policies\ActaPolicy;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property EstadoActa $estado
 * @property NivelCurricular $nivel
 * @property string $nota_maxima
 * @property string $nota_minima_aprobatoria
 * @property list<array{id: int, codigo: string, nombre: string, tipo: string, calculo: ?string, redondeo: string, fuentes: list<array{codigo: string, peso: int}>, ciclo: ?int}> $componentes
 * @property ?CarbonImmutable $validada_en
 * @property ?list<array{nombre: string, cargo: string}> $firmas
 * @property ?string $denominacion
 * @property ?int $ciclo_numero_hasta
 * @property ?string $ciclo_nombre_hasta
 * @property ?int $horas_lectivas_hasta
 */
#[Table('cer_actas')]
#[Fillable([
    'grupo_id', 'periodo_id', 'estado', 'grupo_codigo', 'idioma', 'nivel', 'ciclo_numero', 'ciclo_nombre', 'horas_lectivas', 'periodo',
    'ciclo_numero_hasta', 'ciclo_nombre_hasta', 'horas_lectivas_hasta',
    'docente_id', 'docente_nombre', 'esquema_id', 'esquema_nombre', 'nota_maxima', 'nota_minima_aprobatoria', 'componentes',
    'validada_por', 'validada_en', 'firmas', 'denominacion',
])]
#[UsePolicy(ActaPolicy::class)]
final class Acta extends Model
{
    /**
     * @return HasMany<NotaFinal, $this>
     */
    public function notas(): HasMany
    {
        return $this->hasMany(NotaFinal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validada_por');
    }

    /**
     * @return HasMany<Rectificacion, $this>
     */
    public function rectificaciones(): HasMany
    {
        return $this->hasMany(Rectificacion::class);
    }

    /**
     * @return list<ComponenteDeEvaluacion>
     */
    public function componentesDeEvaluacion(): array
    {
        return array_map(ComponenteDeEvaluacion::deArreglo(...), $this->componentes);
    }

    protected function casts(): array
    {
        return [
            'estado' => EstadoActa::class,
            'nivel' => NivelCurricular::class,
            'ciclo_numero' => 'integer',
            'horas_lectivas' => 'integer',
            'ciclo_numero_hasta' => 'integer',
            'horas_lectivas_hasta' => 'integer',
            'nota_maxima' => 'decimal:2',
            'nota_minima_aprobatoria' => 'decimal:2',
            'componentes' => 'array',
            'firmas' => 'array',
            'validada_en' => 'immutable_datetime',
        ];
    }
}
