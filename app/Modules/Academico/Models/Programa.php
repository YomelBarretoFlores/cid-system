<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\ProgramaFactory;
use App\Modules\Academico\Enums\RegimenDePrograma;
use App\Modules\Academico\Policies\OfertaAcademicaPolicy;
use App\Shared\Support\Eloquent\NormalizaCodigo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property RegimenDePrograma $regimen
 */
#[Table('aca_programas')]
#[Fillable(['codigo', 'nombre', 'regimen', 'activo'])]
#[UseFactory(ProgramaFactory::class)]
#[UsePolicy(OfertaAcademicaPolicy::class)]
final class Programa extends Model
{
    /** @use HasFactory<ProgramaFactory> */
    use HasFactory, NormalizaCodigo;

    #[\Override]
    protected $attributes = [
        'activo' => true,
    ];

    protected function casts(): array
    {
        return [
            'regimen' => RegimenDePrograma::class,
            'activo' => 'boolean',
        ];
    }
}
