<?php

declare(strict_types=1);

namespace App\Modules\Personas\Models;

use App\Modules\Personas\Policies\TipoVinculoPolicy;
use App\Shared\Support\Eloquent\CatalogoAdministrable;
use App\Shared\Support\Eloquent\RenuevaContenidoPublico;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property bool $activo
 * @property bool $por_defecto
 * @property int $orden
 */
#[Table('per_tipos_vinculo')]
#[Fillable(['nombre', 'activo', 'por_defecto', 'orden'])]
#[UsePolicy(TipoVinculoPolicy::class)]
final class TipoVinculo extends Model
{
    use CatalogoAdministrable, RenuevaContenidoPublico;

    #[\Override]
    protected $attributes = [
        'activo' => true,
        'por_defecto' => false,
        'orden' => 0,
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'por_defecto' => 'boolean',
            'orden' => 'integer',
        ];
    }
}
