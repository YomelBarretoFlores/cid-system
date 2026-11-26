<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Models;

use App\Modules\Pagos\Policies\CatalogoDePagosPolicy;
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
 * @property int $orden
 */
#[Table('pag_medios_pago')]
#[Fillable(['nombre', 'activo', 'orden'])]
#[UsePolicy(CatalogoDePagosPolicy::class)]
final class MedioDePago extends Model
{
    use CatalogoAdministrable;
    use RenuevaContenidoPublico;

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }
}
