<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $silabo_id
 * @property string $componente_codigo
 * @property ?string $indicadores
 * @property ?string $instrumento
 * @property ?int $semana
 */
#[Table('doc_silabo_evaluaciones')]
#[Fillable(['silabo_id', 'componente_codigo', 'indicadores', 'instrumento', 'semana'])]
final class EvaluacionDeSilabo extends Model {}
