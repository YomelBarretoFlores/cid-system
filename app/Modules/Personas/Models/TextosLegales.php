<?php

declare(strict_types=1);

namespace App\Modules\Personas\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $politica_privacidad
 * @property string $domicilio_titular
 * @property string $consentimiento_registro
 * @property CarbonImmutable $politica_actualizada_el
 */
#[Table('per_textos_legales')]
#[Fillable(['politica_privacidad', 'domicilio_titular', 'consentimiento_registro', 'politica_actualizada_el'])]
#[WithoutIncrementing]
final class TextosLegales extends Model
{
    public const int FILA = 1;

    protected function casts(): array
    {
        return [
            'politica_actualizada_el' => 'immutable_date',
        ];
    }
}
