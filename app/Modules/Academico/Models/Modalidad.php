<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Policies\OfertaAcademicaPolicy;
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
 * @property ?string $descripcion
 * @property bool $usa_aula
 * @property bool $usa_enlace
 * @property bool $activo
 * @property int $orden
 */
#[Table('aca_modalidades')]
#[Fillable(['nombre', 'descripcion', 'usa_aula', 'usa_enlace', 'activo', 'orden'])]
#[UsePolicy(OfertaAcademicaPolicy::class)]
final class Modalidad extends Model
{
    use CatalogoAdministrable, RenuevaContenidoPublico;

    #[\Override]
    protected $attributes = [
        'activo' => true,
        'orden' => 0,
    ];

    protected function casts(): array
    {
        return [
            'usa_aula' => 'boolean',
            'usa_enlace' => 'boolean',
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }
}
