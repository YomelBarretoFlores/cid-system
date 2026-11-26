<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Models;

use App\Modules\Tramites\Policies\TipoTramitePolicy;
use App\Shared\Support\Eloquent\CatalogoAdministrable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property string $titulo_otorgado
 * @property bool $activo
 * @property int $orden
 */
#[Table('tra_grados_fut')]
#[Fillable(['nombre', 'titulo_otorgado', 'activo', 'orden'])]
#[UsePolicy(TipoTramitePolicy::class)]
final class GradoFut extends Model
{
    use CatalogoAdministrable;

    #[\Override]
    protected $attributes = [
        'activo' => true,
        'orden' => 0,
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }
}
