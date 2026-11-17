<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarbonImmutable $fecha
 * @property string $hora_inicio
 * @property string $hora_fin
 * @property ?CarbonImmutable $llamado_inicio_en
 * @property ?CarbonImmutable $llamado_cierre_en
 * @property bool $registro_posterior
 * @property ?ConfirmacionDeClase $confirmacion
 * @property ?CarbonImmutable $confirmada_en
 * @property ?string $tema
 */
#[Table('doc_clases_dictadas')]
#[Fillable(['grupo_id', 'fecha', 'hora_inicio', 'hora_fin', 'docente_id', 'registrado_por', 'llamado_inicio_en', 'llamado_cierre_en', 'registro_posterior', 'confirmacion', 'confirmada_por_matricula_id', 'confirmada_en', 'observacion', 'tema'])]
final class ClaseDictada extends Model
{
    /**
     * @return HasMany<Asistencia, $this>
     */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'clase_id');
    }

    protected function casts(): array
    {
        return [
            'fecha' => 'immutable_date',
            'llamado_inicio_en' => 'immutable_datetime',
            'llamado_cierre_en' => 'immutable_datetime',
            'registro_posterior' => 'boolean',
            'confirmacion' => ConfirmacionDeClase::class,
            'confirmada_en' => 'immutable_datetime',
        ];
    }
}
