<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Enums\FormaDeAprobacion;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $persona_id
 * @property string $idioma
 * @property NivelCurricular $nivel
 * @property int $ciclo_numero
 * @property string $ciclo_nombre
 * @property ?int $horas_lectivas
 * @property FormaDeAprobacion $forma
 * @property ?string $nota
 * @property CarbonImmutable $fecha
 * @property CarbonImmutable $created_at
 */
#[Table('cer_aprobaciones')]
#[Fillable(['persona_id', 'idioma', 'nivel', 'ciclo_numero', 'ciclo_nombre', 'horas_lectivas', 'forma', 'nota', 'fecha', 'origen_tipo', 'origen_id', 'registrado_por'])]
final class Aprobacion extends Model
{
    protected function casts(): array
    {
        return [
            'nivel' => NivelCurricular::class,
            'ciclo_numero' => 'integer',
            'horas_lectivas' => 'integer',
            'forma' => FormaDeAprobacion::class,
            'nota' => 'decimal:2',
            'fecha' => 'immutable_date',
            'created_at' => 'immutable_datetime',
        ];
    }
}
