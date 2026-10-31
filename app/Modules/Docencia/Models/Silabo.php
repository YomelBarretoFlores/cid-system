<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use App\Modules\Docencia\Enums\EstadoSilabo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $grupo_id
 * @property EstadoSilabo $estado
 * @property ?string $sumilla
 * @property ?string $competencias
 * @property ?string $capacidades
 * @property ?string $metodologia
 * @property ?string $bibliografia
 * @property ?Carbon $publicado_en
 * @property ?int $publicado_por
 */
#[Table('doc_silabos')]
#[Fillable(['grupo_id', 'estado', 'sumilla', 'competencias', 'capacidades', 'metodologia', 'bibliografia', 'publicado_en', 'publicado_por'])]
final class Silabo extends Model
{
    protected $attributes = ['estado' => 'borrador'];

    /**
     * @return HasMany<SemanaDeSilabo, $this>
     */
    public function semanas(): HasMany
    {
        return $this->hasMany(SemanaDeSilabo::class)->orderBy('semana');
    }

    /**
     * @return HasMany<EvaluacionDeSilabo, $this>
     */
    public function evaluaciones(): HasMany
    {
        return $this->hasMany(EvaluacionDeSilabo::class);
    }

    protected function casts(): array
    {
        return ['estado' => EstadoSilabo::class, 'publicado_en' => 'datetime'];
    }
}
