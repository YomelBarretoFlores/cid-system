<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\PeriodoFactory;
use App\Modules\Academico\Enums\EstadoPeriodo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('aca_periodos')]
#[Fillable(['anio', 'numero', 'nombre', 'fecha_inicio', 'fecha_fin', 'matricula_inicio', 'matricula_fin', 'estado'])]
#[UseFactory(PeriodoFactory::class)]
final class Periodo extends Model
{
    /** @use HasFactory<PeriodoFactory> */
    use HasFactory;

    protected $attributes = [
        'estado' => 'planificado',
    ];

    /**
     * @return HasMany<Grupo, $this>
     */
    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'numero' => 'integer',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'matricula_inicio' => 'date',
            'matricula_fin' => 'date',
            'estado' => EstadoPeriodo::class,
        ];
    }
}
