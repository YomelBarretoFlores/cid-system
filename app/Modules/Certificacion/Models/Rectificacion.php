<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable $autorizada_en
 * @property ?CarbonImmutable $cerrada_en
 */
#[Table('cer_rectificaciones')]
#[Fillable(['acta_id', 'motivo', 'autorizada_por', 'autorizada_en', 'cerrada_en'])]
final class Rectificacion extends Model
{
    protected function casts(): array
    {
        return [
            'autorizada_en' => 'immutable_datetime',
            'cerrada_en' => 'immutable_datetime',
        ];
    }
}
