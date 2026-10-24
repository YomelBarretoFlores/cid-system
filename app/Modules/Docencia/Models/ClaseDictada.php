<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarbonImmutable $fecha
 * @property string $hora_inicio
 * @property string $hora_fin
 */
#[Table('doc_clases_dictadas')]
#[Fillable(['grupo_id', 'fecha', 'hora_inicio', 'hora_fin', 'docente_id', 'registrado_por'])]
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
        ];
    }
}
