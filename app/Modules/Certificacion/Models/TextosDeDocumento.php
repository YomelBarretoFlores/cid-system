<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $cierre_constancia_matricula
 * @property string $cierre_constancia_notas
 * @property string $escala_notas
 * @property string $programa_acreditacion
 * @property string $suficiencia_acreditacion
 * @property string $constancia_acta_acreditacion
 * @property string $validez_acreditacion
 * @property string $texto_certificado
 */
#[Table('cer_textos_documentos')]
#[Fillable([
    'cierre_constancia_matricula', 'cierre_constancia_notas', 'escala_notas', 'programa_acreditacion', 'suficiencia_acreditacion',
    'constancia_acta_acreditacion', 'validez_acreditacion', 'texto_certificado',
])]
#[WithoutIncrementing]
final class TextosDeDocumento extends Model
{
    public const int FILA = 1;
}
