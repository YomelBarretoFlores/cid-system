<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\AulaFactory;
use App\Modules\Academico\Policies\OfertaAcademicaPolicy;
use App\Shared\Support\Eloquent\NormalizaCodigo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('aca_aulas')]
#[Fillable(['codigo', 'nombre', 'capacidad', 'ubicacion', 'activo'])]
#[UseFactory(AulaFactory::class)]
#[UsePolicy(OfertaAcademicaPolicy::class)]
final class Aula extends Model
{
    /** @use HasFactory<AulaFactory> */
    use HasFactory, NormalizaCodigo;

    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
            'activo' => 'boolean',
        ];
    }
}
