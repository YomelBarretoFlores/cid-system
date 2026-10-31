<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $silabo_id
 * @property int $semana
 * @property ?string $unidad
 * @property ?string $saber
 * @property ?string $saber_hacer
 * @property ?string $saber_ser
 * @property ?string $actividades
 * @property ?string $recursos
 */
#[Table('doc_silabo_semanas')]
#[Fillable(['silabo_id', 'semana', 'unidad', 'saber', 'saber_hacer', 'saber_ser', 'actividades', 'recursos'])]
final class SemanaDeSilabo extends Model {}
