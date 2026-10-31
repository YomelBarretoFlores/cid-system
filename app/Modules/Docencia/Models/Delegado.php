<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('doc_delegados')]
#[Fillable(['grupo_id', 'matricula_id', 'designado_por'])]
final class Delegado extends Model {}
