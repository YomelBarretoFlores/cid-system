<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $universidad
 * @property string $lema
 * @property string $centro
 * @property string $direccion
 * @property string $telefono
 * @property string $web
 * @property string $facebook
 * @property string $ciudad
 * @property string $formato_numero
 * @property string $formato_numero_constancia_notas
 */
#[Table('cer_datos_institucionales')]
#[Fillable(['universidad', 'lema', 'centro', 'direccion', 'telefono', 'web', 'facebook', 'ciudad', 'formato_numero', 'formato_numero_constancia_notas'])]
#[WithoutIncrementing]
final class Institucion extends Model
{
    public const int FILA = 1;
}
