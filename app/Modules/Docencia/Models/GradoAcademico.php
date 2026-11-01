<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Models;

use App\Modules\Docencia\Policies\CatalogoDeDocenciaPolicy;
use App\Shared\Support\Eloquent\CatalogoAdministrable;
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
#[Table('doc_grados_academicos')]
#[Fillable(['nombre', 'activo', 'orden'])]
#[UsePolicy(CatalogoDeDocenciaPolicy::class)]
final class GradoAcademico extends Model
{
    use CatalogoAdministrable;

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }
}
