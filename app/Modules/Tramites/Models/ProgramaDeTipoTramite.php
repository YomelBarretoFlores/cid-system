<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $tipo_tramite_id
 * @property int $programa_id
 */
#[Table('tra_tipo_tramite_programa')]
#[Fillable(['tipo_tramite_id', 'programa_id'])]
final class ProgramaDeTipoTramite extends Model {}
