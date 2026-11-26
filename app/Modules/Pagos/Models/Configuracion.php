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
 */
#[Table('pag_configuracion')]
#[Fillable(['recargo_matricula_extemporanea'])]
#[WithoutIncrementing]
final class Configuracion extends Model
{
    public const int FILA = 1;

    public const int RECARGO_POR_DEFECTO = 20;

    #[\Override]
    protected $attributes = [
        'recargo_matricula_extemporanea' => self::RECARGO_POR_DEFECTO,
    ];

    protected function casts(): array
    {
        return ['recargo_matricula_extemporanea' => 'integer'];
    }
}
