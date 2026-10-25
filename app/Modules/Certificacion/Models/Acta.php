<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Enums\EstadoActa;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property EstadoActa $estado
 * @property NivelCurricular $nivel
 * @property string $nota_maxima
 * @property string $nota_minima_aprobatoria
 * @property list<array{id: int, codigo: string, nombre: string, peso: string}> $componentes
 * @property ?CarbonImmutable $validada_en
 */
#[Table('cer_actas')]
#[Fillable([
    'grupo_id', 'periodo_id', 'estado', 'grupo_codigo', 'idioma', 'nivel', 'ciclo_numero', 'ciclo_nombre', 'horas_lectivas', 'periodo',
    'docente_id', 'docente_nombre', 'esquema_id', 'esquema_nombre', 'nota_maxima', 'nota_minima_aprobatoria', 'componentes',
    'validada_por', 'validada_en',
])]
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
        return array_map(
            fn (array $componente): ComponenteDeEvaluacion => new ComponenteDeEvaluacion($componente['id'], $componente['codigo'], $componente['nombre'], $componente['peso']),
            $this->componentes,
        );
    }

    protected function casts(): array
    {
        return [
            'estado' => EstadoActa::class,
            'nivel' => NivelCurricular::class,
            'ciclo_numero' => 'integer',
            'horas_lectivas' => 'integer',
            'nota_maxima' => 'decimal:2',
            'nota_minima_aprobatoria' => 'decimal:2',
            'componentes' => 'array',
            'validada_en' => 'immutable_datetime',
        ];
    }
}
