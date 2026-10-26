<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('mat_fichas')]
#[Fillable(['codigo', 'persona_id', 'periodo_id'])]
final class Ficha extends Model {}
