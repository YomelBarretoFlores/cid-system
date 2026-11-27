<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $recargo_matricula_extemporanea
 * @property bool $permite_pagos_anteriores_al_sistema
 */
#[Table('pag_configuracion')]
#[Fillable(['recargo_matricula_extemporanea', 'permite_pagos_anteriores_al_sistema'])]
#[WithoutIncrementing]
final class Configuracion extends Model
{
    public const int FILA = 1;

    public const int RECARGO_POR_DEFECTO = 20;

    #[\Override]
    protected $attributes = [
        'recargo_matricula_extemporanea' => self::RECARGO_POR_DEFECTO,
        'permite_pagos_anteriores_al_sistema' => true,
    ];

    protected function casts(): array
    {
        return ['recargo_matricula_extemporanea' => 'integer', 'permite_pagos_anteriores_al_sistema' => 'boolean'];
    }
}
