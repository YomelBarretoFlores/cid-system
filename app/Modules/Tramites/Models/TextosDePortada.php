<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Models;

use App\Shared\Support\Eloquent\RenuevaContenidoPublico;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nosotros
 * @property list<array{titulo: string, detalle: string}> $pasos_matricula
 * @property list<array{titulo: string, detalle: string}> $pasos_clasificacion
 * @property list<array{nombre: string, descripcion: string, logro: string}> $niveles
 * @property list<array{pregunta: string, respuesta: string}> $preguntas
 */
#[Table('tra_textos_portada')]
#[Fillable(['nosotros', 'pasos_matricula', 'pasos_clasificacion', 'niveles', 'preguntas'])]
#[WithoutIncrementing]
final class TextosDePortada extends Model
{
    use RenuevaContenidoPublico;

    public const int FILA = 1;

    protected function casts(): array
    {
        return [
            'pasos_matricula' => 'array',
            'pasos_clasificacion' => 'array',
            'niveles' => 'array',
            'preguntas' => 'array',
        ];
    }
}
