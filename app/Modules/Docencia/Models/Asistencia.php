<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use App\Modules\Docencia\Enums\EstadoAsistencia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property EstadoAsistencia $estado
 * @property ?EstadoAsistencia $estado_cierre
 */
#[Table('doc_asistencias')]
#[Fillable(['clase_id', 'matricula_id', 'estado', 'estado_cierre'])]
final class Asistencia extends Model
{
    protected function casts(): array
    {
        return [
            'estado' => EstadoAsistencia::class,
            'estado_cierre' => EstadoAsistencia::class,
        ];
    }
}
