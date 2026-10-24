<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\CicloFactory;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Policies\OfertaAcademicaPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('aca_ciclos')]
#[Fillable(['idioma_id', 'numero', 'nivel', 'nombre', 'horas_lectivas', 'activo'])]
#[UseFactory(CicloFactory::class)]
#[UsePolicy(OfertaAcademicaPolicy::class)]
final class Ciclo extends Model
{
    /** @use HasFactory<CicloFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Idioma, $this>
     */
    public function idioma(): BelongsTo
    {
        return $this->belongsTo(Idioma::class);
    }

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'nivel' => NivelCurricular::class,
            'horas_lectivas' => 'integer',
            'activo' => 'boolean',
        ];
    }
}
