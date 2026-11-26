<?php

declare(strict_types=1);

namespace App\Modules\Personas\Models;

use App\Modules\Personas\Policies\TipoDocumentoPolicy;
use App\Shared\Support\Eloquent\CatalogoAdministrable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property bool $solo_digitos
 * @property int $longitud_minima
 * @property int $longitud_maxima
 * @property bool $activo
 * @property bool $por_defecto
 * @property int $orden
 */
#[Table('per_tipos_documento')]
#[Fillable(['nombre', 'solo_digitos', 'longitud_minima', 'longitud_maxima', 'activo', 'por_defecto', 'orden'])]
#[UsePolicy(TipoDocumentoPolicy::class)]
final class TipoDocumento extends Model
{
    use CatalogoAdministrable;

    #[\Override]
    protected $attributes = [
        'solo_digitos' => false,
        'activo' => true,
        'por_defecto' => false,
        'orden' => 0,
    ];

    protected function casts(): array
    {
        return [
            'solo_digitos' => 'boolean',
            'longitud_minima' => 'integer',
            'longitud_maxima' => 'integer',
            'activo' => 'boolean',
            'por_defecto' => 'boolean',
            'orden' => 'integer',
        ];
    }
}
