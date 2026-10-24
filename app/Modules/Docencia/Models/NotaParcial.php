<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property ?string $nota
 */
#[Table('doc_notas_parciales')]
#[Fillable(['grupo_id', 'matricula_id', 'componente_id', 'nota', 'registrado_por'])]
final class NotaParcial extends Model
{
    protected function casts(): array
    {
        return [
            'nota' => 'decimal:2',
        ];
    }
}
