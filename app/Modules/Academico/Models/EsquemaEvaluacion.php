<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\EsquemaEvaluacionFactory;
use App\Modules\Academico\Enums\NivelCurricular;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('aca_esquemas_evaluacion')]
#[Fillable([
    'nombre', 'idioma_id', 'nivel', 'nota_maxima', 'nota_minima_aprobatoria',
    'vigente_desde', 'vigente_hasta',
])]
#[UseFactory(EsquemaEvaluacionFactory::class)]
final class EsquemaEvaluacion extends Model
{
    /** @use HasFactory<EsquemaEvaluacionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Idioma, $this>
     */
    public function idioma(): BelongsTo
    {
        return $this->belongsTo(Idioma::class);
    }

    /**
     * @return HasMany<ComponenteEvaluacion, $this>
     */
    public function componentes(): HasMany
    {
        return $this->hasMany(ComponenteEvaluacion::class, 'esquema_id')->orderBy('orden');
    }

    protected function casts(): array
    {
        return [
            'nivel' => NivelCurricular::class,
            'nota_maxima' => 'decimal:2',
            'nota_minima_aprobatoria' => 'decimal:2',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }
}
