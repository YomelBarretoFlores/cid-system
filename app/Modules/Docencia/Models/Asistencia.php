<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use App\Modules\Docencia\Enums\EstadoAsistencia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property EstadoAsistencia $estado
 */
#[Table('doc_asistencias')]
#[Fillable(['clase_id', 'matricula_id', 'estado'])]
final class Asistencia extends Model
{
    protected function casts(): array
    {
        return [
            'estado' => EstadoAsistencia::class,
        ];
    }
}
