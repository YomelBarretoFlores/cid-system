<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use App\Shared\Support\Eloquent\RenuevaContenidoPublico;
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
 * @property string $resolucion_creacion
 * @property string $base_legal_acreditacion
 */
#[Table('cer_datos_institucionales')]
#[Fillable(['universidad', 'lema', 'centro', 'direccion', 'telefono', 'web', 'facebook', 'ciudad', 'formato_numero', 'formato_numero_constancia_notas', 'resolucion_creacion', 'base_legal_acreditacion'])]
#[WithoutIncrementing]
final class Institucion extends Model
{
    // The public home page prints the university, the center and the city.
    use RenuevaContenidoPublico;

    public const int FILA = 1;
}
